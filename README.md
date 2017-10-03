Symfony Demo Application
========================

The "Symfony Demo Application" is a reference application created to show how
to develop Symfony applications following the recommended best practices.

[![Build Status](https://travis-ci.org/symfony/symfony-demo.svg?branch=master)](https://travis-ci.org/symfony/symfony-demo)

Requirements
------------

  * PHP 7.1.3 or higher;
  * PDO-SQLite PHP extension enabled;
  * and the [usual Symfony application requirements](https://symfony.com/doc/current/reference/requirements.html).

If unsure about meeting these requirements, download the demo application and
browse the `http://localhost:8000/config.php` script to get more detailed
information.

Installation
------------

[![Deploy](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy)

First, install the [Symfony Installer](https://github.com/symfony/symfony-installer)
if you haven't already. Then, install the Symfony Demo Application executing
this command anywhere in your system:

```bash
$ symfony demo

# if you're using Windows:
$ php symfony demo
```

If the `demo` command is not available, update your Symfony Installer to the
most recent version executing the `symfony self-update` command.

> **NOTE**
>
> If you can't use the Symfony Installer, download and install the demo
> application using Git and Composer:
>
>     $ git clone https://github.com/symfony/symfony-demo symfony_demo
>     $ cd symfony_demo/
>     $ composer install --no-interaction

Usage
-----

There is no need to configure a virtual host in your web server to access the application.
Just use the built-in web server:

```bash
$ cd symfony_demo/
$ php bin/console server:run
```

This command will start a web server for the Symfony application. Now you can
access the application in your browser at <http://localhost:8000>. You can
stop the built-in web server by pressing `Ctrl + C` while you're in the
terminal.

> **NOTE**
>
> If you want to use a fully-featured web server (like Nginx or Apache) to run
> Symfony Demo application, configure it to point at the `web/` directory of the project.
> For more details, see:
> https://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html

### Asynchronous processing

This application allows you to purge comments considered as spam using Akismet's API. While using the "Check comments"
button in the backend, this is done synchronously by default. As the processing can be quite slow, it can be nice to
do this processing asynchronously. In order to do so, follow these steps:

1. Pick and install a [Message adapter](https://github.com/sroze/symfony/blob/add-message-component/src/Symfony/Component/Message/README.md#adapters). 
We will use PHP enqueue and its AMQP adapter for this example:
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
$ cd symfony_demo/
$ ./vendor/bin/simple-phpunit
```
