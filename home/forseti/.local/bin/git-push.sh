#!/bin/bash
action=$(yad --width 400 --entry --title "Commit map-tools do Githuba" \
    --button="gtk-ok:0" --button="gtk-cancel:1" \
    --text "Podaj opis commita:" \
    )
ret=$?

if [[ $ret -eq 0 ]]; then
	cd ~/Dokumenty/Kod/PHP/map-tools
	if [[ $(git status --porcelain) ]]; then
		git commit -m "$1"
		git push map-tools master
	fi
fi
