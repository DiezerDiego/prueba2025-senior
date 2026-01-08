FROM ubuntu:latest
LABEL authors="diego"

ENTRYPOINT ["top", "-b"]