#!/usr/bin/env bash
# Prepare the hero drone video and stock photos for the web.
# Needs ffmpeg (brew install ffmpeg / sudo apt install ffmpeg / winget install ffmpeg).
#
#   tools/prep-media.sh video path/to/drone-clip.mp4 [start_seconds] [length_seconds]
#   tools/prep-media.sh image path/to/photo.jpg replacement      # -> public/assets/img/replacement.jpg
#
# Video output: public/assets/video/hero.mp4 (H.264) + hero.webm (VP9), silent, 1920px wide,
# 12s loop by default, plus public/assets/img/hero.jpg as the poster frame.
set -euo pipefail
cd "$(dirname "$0")/.."
OUT_IMG=public/assets/img
OUT_VID=public/assets/video
mkdir -p "$OUT_IMG" "$OUT_VID"

case "${1:-}" in
  video)
    src="$2"; start="${3:-0}"; len="${4:-12}"
    scale="scale='min(1920,iw)':-2:flags=lanczos,fps=30"
    ffmpeg -y -ss "$start" -t "$len" -i "$src" -an -vf "$scale" \
      -c:v libx264 -profile:v high -pix_fmt yuv420p -crf 26 -preset slow -movflags +faststart \
      "$OUT_VID/hero.mp4"
    ffmpeg -y -ss "$start" -t "$len" -i "$src" -an -vf "$scale" \
      -c:v libvpx-vp9 -b:v 0 -crf 36 -row-mt 1 -deadline good \
      "$OUT_VID/hero.webm"
    ffmpeg -y -i "$OUT_VID/hero.mp4" -frames:v 1 -q:v 3 "$OUT_IMG/hero.jpg"
    ls -lh "$OUT_VID" "$OUT_IMG/hero.jpg"
    echo "Aim for hero.mp4 under ~6 MB. If it is bigger, shorten the clip or raise -crf."
    ;;
  image)
    src="$2"; name="$3"
    ffmpeg -y -i "$src" -vf "scale='min(1600,iw)':-2:flags=lanczos" -q:v 4 "$OUT_IMG/$name.jpg"
    ls -lh "$OUT_IMG/$name.jpg"
    ;;
  *)
    sed -n '2,10p' "$0"; exit 1 ;;
esac
echo "Now run: python3 build.py"
