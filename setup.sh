#!/bin/bash
#
# setup.sh
#
# Worklist development environment & sandbox setup script
#

CURRENT_PATH=`pwd`
CURRENT_USER=${WORKLIST_UNIX_USERNAME:=`whoami`}
SANDBOX_NAME=`pwd | sed -r "s:^.+/public_html/::"`
SERVER_NAME=${WORKLIST_SERVER_NAME:="dev.worklist.net"}
SERVER_CONFIG=$CURRENT_PATH/server.local.php
CUSTOM_CONFIG=${WORKLIST_CUSTOM_CONFIG:=""}
TMP_PATH=${WORKLIST_TMP_PATH:=$CURRENT_PATH/tmp}
DEBUG_FILE=${WORKLIST_DEBUG_FILE:=$CURRENT_PATH/php.errors}

# We assume that developers works on their own forked repos 
# so lets keep in sync with the upstream repo
git remote add upstream https://github.com/highfidelity/worklist 2> /dev/null

# pre-commit hook setup to enable trailing whitespaces restrictions on commits
cp $CURRENT_PATH/tools/hooks/pre-commit $CURRENT_PATH/.git/hooks/pre-commit

# Make sure tmp exists and everyone has write permisions there
if [ ! -d  $TMP_PATH ]
then
  mkdir $TMP_PATH
fi
chmod -R 777 $TMP_PATH 2> /dev/null

# download latest composer if not present and makes sure its on the latest version
if [ ! -s $TMP_PATH/composer ]
then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=$TMP_PATH --filename=composer
fi
chmod +x $TMP_PATH/composer
$TMP_PATH/composer self-update

# if there is already a composer installation, let's just update
if [ -s $CURRENT_PATH/composer.lock ]
then
  $TMP_PATH/composer update
fi
# otherwise we need to install
if [ ! -s $CURRENT_PATH/composer.lock ]
then
  $TMP_PATH/composer install
fi

# debugging file
if [ ! -s  $DEBUG_FILE ]
then
  touch $DEBUG_FILE
fi
chmod 777 $DEBUG_FILE

echo "<?php " > $SERVER_CONFIG
echo "ini_set('error_log', '$DEBUG_FILE');" >> $SERVER_CONFIG
echo "define('SANDBOX_USER', '~$CURRENT_USER/');" >> $SERVER_CONFIG
echo "define('SANDBOX_NAME', '$SANDBOX_NAME/');" >> $SERVER_CONFIG
echo "define('APP_LOCATION', SANDBOX_USER . SANDBOX_NAME);" >> $SERVER_CONFIG
echo "define('SERVER_NAME', '$SERVER_NAME');" >> $SERVER_CONFIG
echo "define('SERVER_URL', 'https://' . SERVER_NAME . '/' . APP_LOCATION);" >> $SERVER_CONFIG
echo "define('SECURE_SERVER_URL', SERVER_URL);" >> $SERVER_CONFIG
echo "define('WORKLIST_URL', SECURE_SERVER_URL);" >> $SERVER_CONFIG
echo "define('DEFAULT_SENDER', 'worklist@$SERVER_NAME');" >> $SERVER_CONFIG
echo "define('DEFAULT_SENDER_NAME', 'Worklist [DEV]');" >> $SERVER_CONFIG

if [[ $CUSTOM_CONFIG && -s $CUSTOM_CONFIG ]]
then
  echo "include('$CUSTOM_CONFIG');" >> $SERVER_CONFIG
fi

# setup .htaccess to allow url rewriting
cp .htaccess_default .htaccess
sed -i s/~unixusername/~$CURRENT_USER/g .htaccess
sed -i s/sandboxdir/$SANDBOX_NAME/g .htaccess
sed -i s/#RewriteBase/RewriteBase/g .htaccess