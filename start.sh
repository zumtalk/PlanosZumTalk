#!/bin/bash

# Iniciar o servidor SSH em background
/usr/sbin/sshd

# Iniciar o Apache em foreground (para manter o contêiner rodando)
apache2-foreground