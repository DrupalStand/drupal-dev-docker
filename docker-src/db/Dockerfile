FROM alpine:latest
LABEL maintainer="DrupalStand"

RUN apk --no-cache add --update mariadb mariadb-client pwgen

ADD files/run.sh /scripts/run.sh
ADD conf/map.cnf /etc/my.cnf.d/map.cnf

# Comment out skip-networking
RUN sed -e '/skip-networking/ s/^#*/#/' -i /etc/my.cnf.d/map.cnf && \
    mkdir /scripts/pre-exec.d && \
    mkdir /scripts/pre-init.d && \
    chmod -R 755 /scripts

EXPOSE 3306

VOLUME ["/var/lib/mysql"]

ENTRYPOINT ["/scripts/run.sh"]
