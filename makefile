outfile = Newsletter2GoSW6.zip

$(VERSION): $(outfile)

$(outfile):
	mkdir Newsletter2GoSW6
	cp -R composer.json src  CHANGELOG* Newsletter2GoSW6/
	zip -r  build.zip ./Newsletter2GoSW6
	mv build.zip $(outfile)
	rm -Rf ./Newsletter2GoSW6
