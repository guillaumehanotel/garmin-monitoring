FROM guillaumehanotel/php:8.2-dev-latest

ARG user
ARG uid

# Setup working directory
WORKDIR /var/www/html

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user
RUN echo "$user ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

# Install Python and Pip
RUN apt-get update && apt-get install -y python3 python3-pip

# Install Python dependencies directly
RUN pip3 install --break-system-packages requests python-dotenv garminconnect

# Set user
USER $user
