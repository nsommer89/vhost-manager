FROM ubuntu:22.04

USER root

RUN apt-get update -y && apt-get install -y --no-install-recommends nano curl

EXPOSE 80 22 443