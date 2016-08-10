step by step tutorial:
1. prepare JDK and set the JAVA_HOME into the .profile
2. prepare Tomcat7 into the /opt/server/Tomcat
3. install Opengrok into the /opt/server/opengrok
4. install the ctags:   apt-get install ctags
5. config the environment vars into the .profile like:
    export JAVA_HOME=/opt/jdk1.7.0_60
    export PATH=$JAVA_HOME/bin:$PATH
    export OPENGROK_TOMCAT_BASE=/opt/server/apache-tomcat-7.0.27
    export OPENGROK_INSTANCE_BASE=/opt/server/opengrok
    export SRC_ROOT=/opt/server/opengrok/source
    export DATA_ROOT=/opt/server/opengrok/data
    
6. install apache2  apt-get install apache2
7. install apache mod_jk for tomcat:
   apt-get instal libapache2-mod-jk
8. install apache mod_perl for extension:
   apt-get install libapache2-apache2-mod-perl2
   
9. deploy opengrok
   /opengrok path/Opengrok deploy

10.init the opengrok index:
   /opengrok path/Opengrok index [source path, default in the config .profile SRC_ROOT]
   
11.prepare the jenkins job and assign to this server slave for the routin job to fetch the projects and re-index.

12.apache config set for the projects.

13. perl DBI module install
   apt-get install libapache-dbi-perl



