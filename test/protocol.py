#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from sys import stdout, stderr, argv
import requests


class Protocol:
    cookies = {}

    def __init__(self, base_url):
        self.base_url = base_url;

    def get_url(self):
        return self.base_url + "/export/exchange1c.php"

    def checkauth(self, username, password):
        response = requests.get(self.get_url(),
            params={"type": "sale", "mode": "checkauth"},
            auth=(username, password)
        )

        data = response.text.split("\n")
        status = data[0]

        if status == "success":
            cookie_name = data[1]
            cookie_value = data[2]
            self.cookies[cookie_name] = cookie_value
            return True, None

        elif status == "failure":
            message = data[1]
            return False, "ERROR: %s \n" % message

        return False, "Unknown answer: %s \n" % response.text


if __name__ == "__main__":
    """
    Usage:
    $ python3 protocol.py http://website.ru username password
    """

    url = argv[1]
    username = argv[2]
    password = argv[3]
    protocol = Protocol(url)
    failure = False

    success, message = protocol.checkauth(username, password)
    if not success:
        failure = True
        stdout.write(message)

    if not failure:
        stdout.write("All right, Sir! \n")

