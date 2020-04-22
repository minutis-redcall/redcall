# Slack

If you wish to get error notifications on slack, you should set it up.

1) go to https://api.slack.com/apps and click "Create new app"

![Screenshot](13/13-1.png)

2) enter a name and choose your workspace

![Screenshot](13/13-2.png)

3) click on "oauth & permissions" on left menu

![Screenshot](13/13-3.png)

4) add "chat.write.public" and "chat.write" scopes 

![Screenshot](13/13-4.png)

5) click "install app" 

![Screenshot](13/13-5.png)

6) grant permissions

![Screenshot](13/13-6.png)

7) you now have your slack token 

![Screenshot](13/13-7.png)

### Project configuration

- Set `SLACK_TOKEN` with your Slack token

- Set `SLACK_CHANNEL` you wish to use to receive errors

- Set `SLACK_EMOJI` with any icon you want (to distinguish environments for example)

[Go back](../../README.md)
