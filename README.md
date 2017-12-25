Symfony Demo Application
========================

The "Symfony Demo Application" is a reference application created to show how
to develop Symfony applications following the recommended best practices.

Requirements
------------

  * PHP 7.1.3 or higher;
  * PDO-SQLite PHP extension enabled;
  * and the [usual Symfony application requirements][1].

Installation
------------

Execute this command to install the project:

```bash
$ composer create-project symfony/symfony-demo
```

[![Deploy](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy)

Usage
-----

There's no need to configure anything to run the application. Just execute this
command to run the built-in web server and access the application in your
browser at <http://localhost:8000>:

```bash
$ cd symfony-demo/
$ php bin/console server:run
```

Alternatively, you can [configure a fully-featured web server][2] like Nginx
or Apache to run the application.

### Asynchronous processing

This application allows you to purge comments considered as spam using Akismet's API. While using the "Check comments"
button in the backend, this is done synchronously by default. As the processing can be quite slow, it can be nice to
do this processing asynchronously. 

Pick a [Message adapter](https://github.com/sroze/symfony/blob/add-message-component/src/Symfony/Component/Message/README.md#adapters)
compatible with the Message component, install it, configure it and route some messages to it. This documentation contains 
guides to use Swarrot and Php-Enqueue.

- [With Swarrot](#with-swarrot)
- [With Php-Enqueue](#with-php-enqueue)

#### With Swarrot

1. Install Swarrot's bridge
```
composer req sroze/swarrot-bridge:dev-master
```

2. Configure Swarrot Bundle within your application. 
```yaml
# config/packages/swarrot.yaml
swarrot:
    default_connection: rabbitmq
    connections:
        rabbitmq:
            host: 'localhost'
            port: 5672
            login: 'guest'
            password: 'guest'
            vhost: '/'

    consumers:
        my_consumer:
            processor: app.message_processor
            middleware_stack:
                 - configurator: swarrot.processor.signal_handler
                 - configurator: swarrot.processor.max_messages
                   extras:
                       max_messages: 100
                 - configurator: swarrot.processor.doctrine_connection
                   extras:
                       doctrine_ping: true
                 - configurator: swarrot.processor.doctrine_object_manager
                 - configurator: swarrot.processor.exception_catcher
                 - configurator: swarrot.processor.ack

    messages_types:
        my_publisher:
            connection: rabbitmq # use the default connection by default
            exchange: my_exchange
```

**Important note:** Swarrot will not automatically create the exchanges, queues and bindings for you. You need to manually
configure these within RabbitMq (or another connector you use).

3. Register producer and processor.
```yaml
# config/services.yaml
services:
    # ...
    
    app.message_producer:
        class: Sam\Symfony\Bridge\SwarrotMessage\SwarrotProducer
        arguments:
        - "@swarrot.publisher"
        - "@message.transport.default_encoder"
        - my_publisher

    app.message_processor:
        class: Sam\Symfony\Bridge\SwarrotMessage\SwarrotProcessor
        arguments:
        - "@message_bus"
        - "@message.transport.default_decoder"
```

See that the processor is something Swarrot-specific. As Swarrot's power is to consume messages, we won't use the Message
component's command in this context but Swarrot's command. [Via the bundle configuration](https://github.com/sroze/symfony-demo/blob/6afc2c6116466e1d3610abe7491d40035c0bf4b3/config/packages/swarrot.yaml#L14),
we configure Swarrot to use our processor.

4. Route your messages to the bus
```yaml
# config/packages/framework.yaml
    message:
        routing:
            'App\Message\CheckSpamOnPostComments': app.message_producer
```

5. Consume your messages!
```bash
bin/console swarrot:consume:my_consumer queue_name_you_created
```

#### With Php-Enqueue

1. Install the enqueue bridge and the enqueue AMQP extension. (Note that you can use any of the multiple php-enqueue extensions)

```
composer req sroze/enqueue-bridge:dev-master enqueue/amqp-ext
```

2. Configure the adapter.
```yaml
# config/packages/enqueue.yaml
enqueue:
    transport:
        default: 'amqp'
        amqp:
            host: 'localhost'
            port: 5672
            user: 'guest'
            pass: 'guest'
            vhost: '/'
            receive_method: basic_consume
    client: ~
```

3. Register your consumer & producer
```yaml
# config/services.yaml
services:
    app.amqp_consumer:
        class: Sam\Symfony\Bridge\EnqueueMessage\EnqueueConsumer
        arguments:
        - "@message.transport.default_decoder"
        - "@enqueue.transport.amqp.context"
        - "messages" # Name of the queue

    app.amqp_producer:
        class: Sam\Symfony\Bridge\EnqueueMessage\EnqueueProducer
        arguments: 
        - "@message.transport.default_encoder"
        - "@enqueue.transport.amqp.context"
        - "messages" # Name of the queue
```

4. Route your messages to the consumer
```yaml
# config/packages/framework.yaml
framework:
    message:
        routing:
            'App\Message\CheckSpamOnPostComments': app.amqp_producer
```

You are done. The `CheckSpamOnPostComments` messages will be sent to your local RabbitMq instance. In order to process
them asynchronously, you need to consume the messages pushed in the queue. You can start a worker with the `message:consume`
command:

```bash
bin/console message:consume --consumer=app.amqp_consumer
```

**Note:** if you don't have RabbitMq installed locally but have Docker, run a RabbitMq instance this way:
```sh
docker run -d --hostname rabbit --name rabbit -p 8080:15672 -p 5672:5672 rabbitmq:3-management
```
The administration console will be accessible at http://localhost:8080 

Testing
-------

Execute this command to run tests:

```bash
$ cd symfony-demo/
$ ./vendor/bin/simple-phpunit
```

[1]: https://symfony.com/doc/current/reference/requirements.html
[2]: https://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html
