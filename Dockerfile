#FROM php:7.4-apache
FROM php:7.3-apache
#FROM php:7.2-apache
RUN apt-get update && apt upgrade -y
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli
#ADD ./app /var/www/html
#RUN mkdir /var/www/html/v1
#RUN mkdir /var/www/html/v2
#COPY ./install-wp-tests.sh /var/www/html/v2/install-wp-tests.sh
COPY ./my-site.conf /etc/apache2/sites-available/my-site.conf
RUN echo 'SetEnv MYSQL_DB_CONNECTION ${MYSQL_DB_CONNECTION}' >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'SetEnv MYSQL_DB_NAME ${MYSQL_DB_NAME}' >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'SetEnv MYSQL_USER ${MYSQL_USER}' >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'SetEnv MYSQL_PASSWORD ${MYSQL_PASSWORD}' >> /etc/apache2/conf-enabled/environment.conf
RUN echo 'SetEnv SITE_URL ${SITE_URL}' >> /etc/apache2/conf-enabled/environment.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf &&\
    a2enmod rewrite &&\
    a2enmod headers &&\
    a2enmod rewrite &&\
    a2dissite 000-default &&\
    a2ensite my-site &&\
    service apache2 restart
#RUN apt-get install subversion -y
#RUN apt-get install dos2unix -y
RUN apt-get install mc -y
RUN apt-get install cron -y
#RUN dos2unix /var/www/html/v2/install-wp-tests.sh
#RUN apt-get install default-mysql-client -y
#RUN /var/www/html/v2/install-wp-tests.sh test root test
EXPOSE 80
EXPOSE 3001
#EXPOSE 3002