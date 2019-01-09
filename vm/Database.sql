CREATE DATABASE dalamud;
CREATE USER dalamud@localhost IDENTIFIED BY 'dalamud';
GRANT ALL PRIVILEGES ON *.* TO dalamud@'%' IDENTIFIED BY 'dalamud';
GRANT ALL PRIVILEGES ON *.* TO dalamud@localhost IDENTIFIED BY 'dalamud';
FLUSH PRIVILEGES;
