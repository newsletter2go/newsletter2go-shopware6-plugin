outfile = shopware6-nl2go-$(VERSION).zip

$(VERSION): $(outfile)

$(outfile):
	mkdir newsletter2go
	cp -R composer.json src  CHANGELOG* newsletter2go/
	zip -r  build.zip ./newsletter2go
	mv build.zip $(outfile)
	rm -Rf ./newsletter2go
