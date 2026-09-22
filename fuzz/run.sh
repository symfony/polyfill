#!/bin/sh

set -eu

fuzz_dir=$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)
campaign_dir="$fuzz_dir/campaigns"

available_campaigns()
{
    for campaign_file in "$campaign_dir"/*.php; do
        [ -f "$campaign_file" ] || continue
        basename "$campaign_file" .php
    done
}

if [ "$#" -eq 0 ]; then
    echo "Usage: fuzz/run.sh <campaign> [php-fuzzer options...]" >&2
    echo "Available campaigns:" >&2
    available_campaigns | sed 's/^/  /' >&2
    exit 2
fi

target=$1
shift
case "$target" in
    ''|*[!A-Za-z0-9_-]*)
        echo "Unknown differential target: $target" >&2
        exit 2
        ;;
esac

target_file="$campaign_dir/$target.php"
if [ ! -f "$target_file" ]; then
    echo "Unknown differential target: $target" >&2
    echo "Available campaigns:" >&2
    available_campaigns | sed 's/^/  /' >&2
    exit 2
fi

if [ -x "$fuzz_dir/vendor/bin/php-fuzzer" ]; then
    php_fuzzer="$fuzz_dir/vendor/bin/php-fuzzer"
elif command -v php-fuzzer >/dev/null 2>&1; then
    php_fuzzer=php-fuzzer
else
    echo "php-fuzzer is not installed; run: composer --working-dir=fuzz install" >&2
    exit 127
fi

run_dir="$fuzz_dir/runs/$target"
corpus_dir="$fuzz_dir/corpus/$target"
seed_dir="$fuzz_dir/seeds/$target"

if [ ! -d "$corpus_dir" ]; then
    mkdir -p "$corpus_dir"
    if [ -d "$seed_dir" ]; then
        for seed in "$seed_dir"/*; do
            [ -f "$seed" ] || continue
            cp "$seed" "$corpus_dir/"
        done
    fi
fi

mkdir -p "$run_dir"
cd "$run_dir"

exec "$php_fuzzer" fuzz "$@" "$target_file" "$corpus_dir"
