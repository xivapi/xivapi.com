#!/usr/bin/env bash

#
# Setup
#
echo "Setting up"
cd /vagrant
USER=vagrant
sudo locale-gen en_GB.UTF-8
sudo apt-get update -y
sudo apt-get upgrade -y
sudo apt-get install -y htop unzip curl git dos2unix

#
# NGINX
#
echo "Installing: NGINX"
sudo add-apt-repository ppa:nginx/stable -y
sudo apt-get update
sudo apt-get install -y nginx
rm /etc/nginx/sites-available/default
sudo cp /vagrant/vm/VagrantfileNginxCommon /etc/nginx/sites-available/common
sudo cp /vagrant/vm/VagrantfileNginxDefault /etc/nginx/sites-available/default
sudo cp /vagrant/vm/VagrantfileNginx.conf /etc/nginx/nginx.conf

#
# PHP + Composer + Imagick
#
echo "Installing: PHP + Composer"
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update -y
sudo apt-get install -y \
    php7.4-fpm \
    php-apcu \
    php-imagick \
    php7.4-dev \
    php7.4-cli \
    php7.4-json \
    php7.4-intl \
    php7.4-mysql \
    php7.4-sqlite \
    php7.4-curl \
    php7.4-gd \
    php7.4-mbstring \
    php7.4-dom \
    php7.4-xml \
    php7.4-zip \
    php7.4-tidy \
    php7.4-bcmath


sudo sed -i 's|display_errors = Off|display_errors = On|' /etc/php/7.4/fpm/php.ini
sudo sed -i 's|memory_limit = 128M|memory_limit = -1|' /etc/php/7.4/fpm/php.ini
sudo sed -i "s|www-data|$USER|" /etc/php/7.4/fpm/pool.d/www.conf

#
# MySQL
#
echo "Installing: MySQL"
echo "mysql-server mysql-server/root_password password dalamud" | debconf-set-selections
echo "mysql-server mysql-server/root_password_again password dalamud" | debconf-set-selections
sudo apt install mysql-server -y
mysql -uroot -pdalamud < /vagrant/vm/Database.sql

#
# Redis
#
echo "Installing: Redis"
sudo apt-get install redis-server -y
git clone https://github.com/phpredis/phpredis.git
cd phpredis && phpize && ./configure && make && sudo make install
cd /vagrant
rm -rf /vagrant/phpredis
sudo echo "extension=redis.so" > /etc/php/7.4/mods-available/redis.ini
sudo ln -sf /etc/php/7.4/mods-available/redis.ini /etc/php/7.4/fpm/conf.d/20-redis.ini
sudo ln -sf /etc/php/7.4/mods-available/redis.ini /etc/php/7.4/cli/conf.d/20-redis.ini
sudo service php7.4-fpm restart

#
# Install JAVA + ElasticSearch
#
echo "Installing: Java + ElasticSearch"
export _JAVA_OPTIONS="-Xmx1g -Xms1g"
sudo apt install -y openjdk-8-jre apt-transport-https
sudo wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
sudo bash -c 'echo "deb https://artifacts.elastic.co/packages/7.x/apt stable main" | sudo tee /etc/apt/sources.list.d/elastic-7.x.list'
sudo apt-get update -y && sudo apt-get install elasticsearch -y
sudo sed -i 's|#network.host: 192.168.0.1|network.host: 0.0.0.0|g' /etc/elasticsearch/elasticsearch.yml
sudo systemctl start elasticsearch

#
# Finish
#
echo "Finishing up ..."
sudo mkdir -p /vagrant_xivapi /vagrant_mogboard
sudo chown vagrant:vagrant /vagrant_xivapi /vagrant_mogboard
sudo chmod -R 777 /vagrant_xivapi /vagrant_mogboard
sudo service nginx restart
sudo service php7.4-fpm restart
sudo apt-get autoremove -y
sudo apt-get update -y
sudo apt-get upgrade -y

bash /vagrant/bin/version

echo "- Testing ElasticSearch in 10 seconds ..."
sleep 10
curl -X GET 'http://localhost:9200'
