outfile = shopware6-nl2go-$(VERSION).zip

$(VERSION): $(outfile)

$(outfile):
	mkdir newsletter2go
	cp -R LICENSE composer.json src newsletter2go/
	zip -r  build.zip ./newsletter2go
	mv build.zip $(outfile)
	rm -Rf ./newsletter2go
