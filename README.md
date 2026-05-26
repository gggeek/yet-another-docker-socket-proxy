# Yet Another Docker Socket Proxy

Yet another security-enhancing proxy for the Docker Socket.

Aka. a small forward proxy for filtering the responses of calls to the Docker API to only what you want to expose.

It is a "level 7" proxy, in that it is aware of the semantics of the Docker API protocol, and it allows both filtering
the Requests and rewriting the Responses.


## Why ?

"Giving access to your Docker socket could mean giving root access to your host, or even to your whole swarm, but some
services require hooking into that socket to react to events, etc. Using a proxy lets you block anything you consider
those services should not do." (docker-socket-proxy readme)

There are multiple, mature Docker Socket Proxy projects already.

What this one does different is that it allows both filtering the Requests and rewriting the Responses.

This means f.e. restricting a client to
- execute a call to GET /containers/json and be able to see only a specific list of containers
- inspect/act upon only a specific set of containers

It was inspired by https://blog.foxxmd.dev/posts/restricting-socket-proxy-by-container/

## WORK IN PROGRESS !!!

The following features are not implemented yet:
- filtering requests based on docker tags
- filtering the responses
- installation as standalone executable or via a prebuilt container

*Stay tuned*

## Installation

### As Docker Container

There are no images produced yet.

For now:
1. `git clone` this project
2. run `composer install --classmap-authoritative`
3. run
   ```shell
   sudo docker run \
       --rm \
       -p 0.0.0.0:2375:2375 \
       -v .:/app:ro \
       -v ./docker/Caddyfile:/etc/frankenphp/Caddyfile:ro \
       -v /var/run/docker.sock:/var/run/docker.sock:ro \
       -e YADSP_CONFIG='[[{"some_filter": "some value"}]]'
       dunglas/frankenphp
   ```
4. export `DOCKER_HOST=tcp://127.0.0.1:2375`
5. test: `sudo docker ps` - this should not show any containers, despite the fact that there is one running,
   whereas `sudo docker version` should show the Server information

### With FrankenPHP
...
### With another webserver
...

## Configuration

The configuration can be passed in to the container as a string, via environment variable `YADSP_CONFIG`, or via a file.
The file can be mounted into the container via `-v`, and its location specified via env var `YADSP_CONFIG_FILE`, eg:
```shell
    -e YADSP_CONFIG_FILE=/tmp/yadsp_fw_config.json \
    -v ./config.json:/tmp/yadsp_fw_config.json:ro \
```

The format to be used in both cases is Json.

The configuration format is:
- a json object, where each element is a rule (the names of rules are not important - they are mostly of use to debug configuration errors)
- every rule is an array, each element of which is a match condition
- a match condition is a json object, with the key being the type of match, and the value generally being a single string,
  or an array of strings.

The logic applied is:
- by default the firewall only allows access to the docker '/version' and '/ping api calls', everything else gets a 404
  response
- the rules are evaluated in the order that they are defined
- for each rule, all its conditions are verified
- if the conditions match, the request is allowed to go through

The supported match conditions are:
- `client_address` - a string or array of strings. IPv4 only for now. '*' wildcards supported
- `client_port` - an integer or array of integers.
- `http_method` - a string or array of strings. Only exact matching. Eg: `HEAD`, `GET`, ...
- `url` - a string or array of strings. WIP...
- `user_agent` - a string or array of strings. '*' wildcards supported

## Example rule sets

NB: see the [Docker API reference](https://docs.docker.com/reference/api/engine) for details about which requests to whitelist.

### Allow full access, read-only

```json
[[{"http_method":  ["GET", "HEAD"]}]]
```

This relies on the Docker API following RESTful semantics.

## Design principles
- php 8.2 and up
- compact code. Trying not to rely on too many dependencies
- hackable. Using the psr-7, psr-15, psr-18 means it should be easy to extend/embed the Proxy class in other middlewares
- maximum speed of execution / minimum possible memory usage are not the main target. Safety, robustness and flexibility
  come first
- good error messages. Better overly verbose than cryptic

## FAQ
...
