distr:
	rm -rf /tmp/boomstarter.gifts
	mkdir -p /tmp/boomstarter.gifts/.lastversion
	cp -r ./boomstarter.gifts /tmp/boomstarter.gifts/.lastversion
	cd /tmp/boomstarter.gifts/ ; zip -r boomstarter.gifts.zip .lastversion/
	mv /tmp/boomstarter.gifts/boomstarter.gifts.zip boomstarter.gifts.zip

