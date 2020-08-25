FROM node:13

COPY . .

RUN npm install

ENV BOTUSERNAME=your_bot_username
ENV BOTPASSWORD=oauth:your_oauth_token
ENV BOTCHANNEL=your_channel
ENV URLBASE=https://url.to.your.request.scripts/
ENV SECRET_KEY=your-secret-key

CMD npm start