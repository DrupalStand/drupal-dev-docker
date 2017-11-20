FROM docker.elastic.co/elasticsearch/elasticsearch:6.0.0

RUN yum install -y\
  epel-release

RUN yum install nodejs npm --enablerepo=epel -y

RUN npm install -g elasticdump

EXPOSE 9200
