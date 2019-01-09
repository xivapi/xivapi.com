#!/usr/bin/env bash

# https://www.howtoforge.com/tutorial/how-to-set-up-rabbitmq-cluster-on-ubuntu-1804-lts/
echo 10.0.15.21 hakase-ubuntu01 >> sudo vim /etc/hosts
echo 10.0.15.22 hakase-ubuntu02 >> sudo vim /etc/hosts
echo 10.0.15.23 hakase-ubuntu03 >> sudo vim /etc/hosts

sudo apt update -y
sudo apt install rabbitmq-server -y
sudo systemctl start rabbitmq-server
sudo systemctl enable rabbitmq-server
sudo rabbitmq-plugins enable rabbitmq_management
sudo systemctl restart rabbitmq-server

sudo ufw allow ssh
sudo ufw enable
sudo ufw allow 5672,15672,4369,25672/tcp
sudo ufw status

sudo rabbitmqctl add_user dalamud ZBrkytTOi0HgS2f9olAKz66LBmnuR2
sudo rabbitmqctl set_user_tags dalamud administrator
sudo rabbitmqctl set_permissions -p / dalamud ".*" ".*" ".*"
sudo rabbitmqctl delete_user guest
sudo rabbitmqctl list_users
sudo systemctl restart rabbitmq-server

# http://<ip>:15672/
