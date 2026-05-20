# Docker Proxy Filter PHP

Yet another security-enhancing proxy for the Docker Socket.

## Why ?

"Giving access to your Docker socket could mean giving root access to your host, or even to your whole swarm, but some
services require hooking into that socket to react to events, etc. Using a proxy lets you block anything you consider
those services should not do." (docker-socket-proxy readme)

There are multiple, mature Docker Socket Proxy projects already.

What this one does different is that it allows both filtering the Requests and rewriting the Responses.

This means f.e. restricting a client to
- execute a call to GET /containers/json and be able to see only a specific list of containers
- inspect/act upon only a specific list of containers

## WORK In PROGRESS

This is meant to be:
- a rewrite of https://github.com/FoxxMD/docker-proxy-filter in PHP, to compare LoCs and runtime latency/scalability/memory
- a playground for getting my feet wet with FrankenPHP woker mode and embedded scripts

A small, forward proxy for filtering the content and responses of Docker API responses to only those you want to expose.

*Stay tuned*
