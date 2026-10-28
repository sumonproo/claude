"""Write compositions/lyrics.html from assets/music/lyrics.json.

Usage: python3 build_lyrics.py <lyrics.json> <out.html> <film_seconds>
"""
import json
import sys

src, out, film = sys.argv[1], sys.argv[2], float(sys.argv[3])
lines = json.load(open(src))["lines"]
SPLIT = [4, 4, 4, 4]  # words on the first row of each line
LEAD = 0.25  # line opens this long before its first word

opens = [round(l[0]["start"] - LEAD, 3) for l in lines]
closes = opens[1:] + [film]

groups, tweens = [], []
for i, words in enumerate(lines):
    o, c = opens[i], closes[i]
    rows = [words[: SPLIT[i]], words[SPLIT[i] :]]
    row_html = "\n".join(
        '          <div class="ly-row">'
        + " ".join(f'<span class="w" id="{w["id"]}">{w["text"]}</span>' for w in row)
        + "</div>"
        for row in rows
    )
    groups.append(
        f'    <div id="ly-l{i}" class="clip ly-line" data-start="{o}" data-duration="{round(c - o, 3)}">\n'
        f'      <div class="ly-inner" id="ly-l{i}-in">\n{row_html}\n      </div>\n    </div>'
    )
    tweens.append(f"    // Line {i + 1}")
    tweens.append(
        f'    tl.fromTo("#ly-l{i}-in", {{ opacity: 0, filter: "blur(8px)" }}, '
        f'{{ opacity: 1, filter: "blur(0px)", duration: 0.22, ease: "power4.out" }}, {o});'
    )
    for w in words:
        s, e = w["start"], w["end"]
        tweens.append(
            f'    sing("#{w["id"]}", {s}, {e});'
        )
    tweens.append(
        f'    tl.to("#ly-l{i}-in", {{ opacity: 0, filter: "blur(8px)", duration: 0.18, ease: "power2.in" }}, {round(c - 0.2, 3)});'
    )
    tweens.append(f'    tl.set("#ly-l{i}-in", {{ opacity: 0, visibility: "hidden" }}, {round(c - 0.01, 3)});')

html = f"""<template>
  <style>
    #root {{
      position: absolute;
      inset: 0;
    }}
    .ly-line {{
      position: absolute;
      left: 0;
      right: 0;
      top: 700px;
      text-align: center;
      overflow: visible;
    }}
    .ly-inner {{
      display: inline-block;
      padding: 26px 46px 30px;
      border-radius: 36px;
      background: rgb(10 26 23 / 0.46);
    }}
    .ly-row {{
      font-size: 80px;
      font-weight: 700;
      font-style: italic;
      line-height: 1.12;
      letter-spacing: -0.01em;
      white-space: nowrap;
    }}
    .ly-row .w {{
      display: inline-block;
      color: rgb(255 255 255 / 0.75);
      text-shadow: 0 2px 18px rgb(0 0 0 / 0.35);
    }}
  </style>
  <div id="root" data-composition-id="lyrics" data-width="1080" data-height="1920">
{chr(10).join(groups)}
  </div>
  <script>
    const tl = gsap.timeline({{ paused: true }});
    const hl =
      getComputedStyle(document.documentElement).getPropertyValue("--highlight").trim() || "#ffd27a";
    // Karaoke: each word lights up in the highlight colour on its melody note, then settles to white
    function sing(sel, start, end) {{
      tl.fromTo(sel, {{ color: "rgba(255,255,255,0.75)", scale: 1 }},
        {{ color: hl, scale: 1.08, duration: 0.09, ease: "power3.out" }}, start);
      tl.to(sel, {{ color: "#ffffff", scale: 1, duration: 0.3, ease: "power2.out" }}, Math.max(start + 0.12, end - 0.1));
    }}
{chr(10).join(tweens)}
    window.__timelines["lyrics"] = tl;
  </script>
</template>
"""
open(out, "w").write(html)
print("wrote", out, "lines", len(lines), "opens", opens)
