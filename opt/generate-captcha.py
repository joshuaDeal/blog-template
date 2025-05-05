#!/bin/env python3
# Script to make textual image captchas.

import sys
from io import BytesIO
from captcha.image import ImageCaptcha

file = "captcha.png"

for i in range(len(sys.argv)):
	if sys.argv[i] == "--help" or sys.argv[i] == '-h':
		print(sys.argv[0])
		print("Usage:  " + sys.argv[0], "--[option]")
		print("Options:")
		print("\t--help\tDisplay this help message.")
		print("\t--file\tSpecify the output file.")
		print("\t--text\tSpecify the captcha text.")
		sys.exit()

	if sys.argv[i] == "--text" or sys.argv[i] == '-t':
		text = sys.argv[i + 1]

	if sys.argv[i] == "--file" or sys.argv[i] == '-f':
		file = sys.argv[i + 1]

captcha = ImageCaptcha()
data: BytesIO = captcha.generate(text)

with open(file, 'wb') as file:
	file.write(data.getvalue())
