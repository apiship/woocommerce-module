#!/bin/sh
# Сборка релизного zip плагина WP ApiShip.
# Внутри архива — папка apiship/, чтобы при установке через админку WP
# плагин ложился в wp-content/plugins/apiship/ (заменяя существующий),
# а не в каталог с именем zip-файла.
set -e

REPO_DIR="$(cd "$(dirname "$0")" && pwd)"
VERSION="$(grep -m1 "Version:" "$REPO_DIR/wp-apiship.php" | sed 's/[^0-9.]*//g')"
OUT="$REPO_DIR/apiship-$VERSION.zip"

rm -f "$OUT"
git -C "$REPO_DIR" archive --format=zip --prefix=apiship/ -o "$OUT" HEAD \
	":(exclude)CLAUDE.md" \
	":(exclude).gitignore" \
	":(exclude)build-release.sh"

echo "Собрано: $OUT"
unzip -l "$OUT" | head -8
