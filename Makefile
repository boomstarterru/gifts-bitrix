distr:
	rm -rf /tmp/boomstarter.gifts
	mkdir -p /tmp/boomstarter.gifts/.lastversion
	cp -r ./boomstarter.gifts /tmp/boomstarter.gifts/.lastversion
	cd /tmp/boomstarter.gifts/ ; zip -r boomstarter.gifts.zip .lastversion/
	mv /tmp/boomstarter.gifts/boomstarter.gifts.zip boomstarter.gifts.zip

lang:
	cd boomstarter.gifts/lang ; rm -Rf ru.CP1251
	cd boomstarter.gifts/lang ; cp -R ru.UTF-8 ru.windows-1251
	cd boomstarter.gifts/lang/ru.UTF-8 ; find . -name "*.php" -exec iconv -f UTF-8 -t CP1251 {} -o ./../ru.windows-1251/{} \;
	cd boomstarter.gifts/lang ; git add -A . ; git commit . -m "lang"