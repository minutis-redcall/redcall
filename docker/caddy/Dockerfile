FROM caddy:2

COPY Caddyfile /etc/caddy/Caddyfile

WORKDIR /var/www/symfony

CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
