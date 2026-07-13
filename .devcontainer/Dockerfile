# syntax=docker/dockerfile:1.4

FROM mcr.microsoft.com/devcontainers/base:ubuntu

RUN export DEBIAN_FRONTEND=noninteractive

# Install the xz-utils package
# RUN apt-get update && apt-get install -y xz-utils

ARG USERNAME=vscode

# expose PHP xdebug port 9003
EXPOSE 9003

# expose wp-env ports
EXPOSE 8888
EXPOSE 8889

# Install Node.js (required for pnpm)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Playwright/Chromium system libraries (ubuntu26 arm64 has no playwright install-deps support)
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    libglib2.0-0t64 \
    libnspr4 \
    libnss3 \
    libatk1.0-0t64 \
    libatk-bridge2.0-0t64 \
    libatspi2.0-0t64 \
    libx11-6 \
    libxcomposite1 \
    libxdamage1 \
    libxext6 \
    libxfixes3 \
    libxrandr2 \
    libgbm1 \
    libxcb1 \
    libxkbcommon0 \
    libasound2t64 \
    libcups2t64 \
    libcairo2 \
    libpango-1.0-0 \
    libgtk-3-0t64 \
    libdrm2 \
    fonts-liberation \
    && rm -rf /var/lib/apt/lists/*

# enable persisted bash completion
# https://code.visualstudio.com/remote/advancedcontainers/persist-bash-history
RUN SNIPPET="export PROMPT_COMMAND='history -a' && export HISTFILE=/commandhistory/.bash_history" \
    && mkdir /commandhistory \
    && touch /commandhistory/.bash_history \
    && chown -R $USERNAME /commandhistory \
    && echo "$SNIPPET" >> "/home/$USERNAME/.bashrc"

# create message to show at first vscode run
# COPY .devcontainer/welcome.txt /usr/local/etc/vscode-dev-containers/first-run-notice.txt
COPY <<EOF /usr/local/etc/vscode-dev-containers/first-run-notice.txt
# Setup

At first time you need to install the dependencies using the "pnpm install" command.

# Development

Run "pnpm start" to start the WordPress development environment.

Execute "jq '.scripts' package.json" to see available scripts.

Have fun !

EOF

USER $USERNAME

# Install pnpm
RUN curl https://get.pnpm.io/install.sh | ENV="$HOME/.bashrc" PNPM_VERSION=9.9.0 SHELL="$(which bash)" bash -
ENV PATH="/home/$USERNAME/.local/share/pnpm:${PATH}"