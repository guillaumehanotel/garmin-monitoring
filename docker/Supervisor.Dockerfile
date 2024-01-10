FROM guillaumehanotel/php:8.2-dev-latest

ARG user
ARG uid

# Create user
RUN useradd -rm -d /home/$user -s /bin/bash -u $uid -g root -G www-data,root,sudo $user

RUN echo "$user ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

### Crontab
COPY scheduler /etc/cron.d/scheduler
RUN chmod 0644 /etc/cron.d/scheduler \
    && crontab /etc/cron.d/scheduler

# Copy Supervisor Conf
COPY supervisor /etc/supervisor

CMD supervisord -n -c /etc/supervisor/supervisord.conf

# Install Python and Pip
RUN apt-get update && apt-get install -y python3 python3-pip

# Install Python dependencies directly
RUN pip3 install --break-system-packages requests python-dotenv garminconnect
