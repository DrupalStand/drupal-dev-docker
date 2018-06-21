FROM alpine:latest
LABEL maintainer="DrupalStand"

RUN apk --no-cache add --update mariadb mariadb-client pwgen

ADD files/run.sh /scripts/run.sh
ADD conf/map.cnf /etc/mysql/conf.d/map.cnf

RUN mkdir /scripts/pre-exec.d && \
    mkdir /scripts/pre-init.d && \
    chmod -R 755 /scripts

EXPOSE 3306

VOLUME ["/var/lib/mysql"]

ENTRYPOINT ["/scripts/run.sh"]
