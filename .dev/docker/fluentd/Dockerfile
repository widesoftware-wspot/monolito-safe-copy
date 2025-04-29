FROM fluentd:latest
USER root

ENV AWS_ACCESS_KEY=""
ENV AWS_SECRET_KEY=""
ENV AWS_BUCKET=""
ENV AWS_REGION=""

RUN apk add --update alpine-sdk
RUN apk add --update ruby-dev
RUN fluent-gem install fluent-plugin-s3
RUN fluent-gem install fluent-plugin-kafka -v 0.12.3