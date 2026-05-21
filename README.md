# Docker Proxy Filter PHP

Yet another security-enhancing proxy for the Docker Socket.

Aka. a small forward proxy for filtering the responses of calls to the Docker API to only what you want to
expose.

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

## WORK In PROGRESS

This is meant to be:
- a rewrite of https://github.com/FoxxMD/docker-proxy-filter in PHP, to compare LoCs and runtime latency/scalability/memory
- a playground for getting my feet wet with FrankenPHP woker mode and embedded scripts
- an MVP project

*Stay tuned*

## Installation

### As Docker Container

There are no images produced yet.

For now:
1. `git clone` this project
2. run
   ```shell
   docker run \
     --rm \
     -v .:/app \
     -p 2375:80 \
     -p 443:443 \
     -p 443:443/udp \
     -e SERVER_NAME=$(hostname) \
     -v /var/run/docker.sock:/var/run/docker.sock \
     -v ./dpfp.log:/tmp/dpfp.log \
     dunglas/frankenphp
   ```
3. export `DOCKER_HOST=tcp://127.0.0.1:2375`
4. test: `sudo docker ps` -> this should not show any containers, despite the fact that there is one running

### With FrankenPHP
...
### With another webserver
...

## Configuration
...

## Example rule sets
...

## Design principles
- php 8.2 and up
- compact code. Trying not to rely on too many dependencies
- hackable. Using the psr-7, psr-15, psr-18 means it should be easy to extend/embed the Proxy class in other middlewares

## FAQ
...
