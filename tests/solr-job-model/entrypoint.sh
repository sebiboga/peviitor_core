#!/bin/sh
set -e

# Pornește serverul web pe fundal
php -S 0.0.0.0:8900 index.php &

# Rulează testele o dată la pornire
php run.php

# Ține containerul în viață cât timp rulează serverul
wait
