# pIqaD font

To enable Klingon **pIqaD** rendering, drop one or more pIqaD font files in
this folder. The plugin looks for these filenames, in order of preference:

- `pIqaD.woff2` (best — smallest, all modern browsers since ~2018)
- `pIqaD.woff`  (legacy fallback — IE9+, old Safari/iOS)
- `pIqaD.ttf`   (universal fallback)

You can ship one, two, or all three — the bundled CSS lists all three formats
in the `@font-face` declaration, so each browser picks the most efficient one
it supports.

If none of these files are present, the *Render in pIqaD script* admin setting
will still appear but show a notice asking for a font, and the body falls back
to the system font (Latin Klingon stays readable).

## Generating .woff2 / .woff from a .ttf

If you only have a TTF, generate the smaller modern formats once:

```sh
# WOFF2 (modern browsers, smallest):
brew install woff2
woff2_compress pIqaD.ttf      # produces pIqaD.woff2

# WOFF (legacy fallback):
pip3 install --user fonttools brotli
python3 -c "from fontTools.ttLib import TTFont; f=TTFont('pIqaD.ttf'); f.flavor='woff'; f.save('pIqaD.woff')"
```

Typical sizes for the bundled HaSta font: ~98K TTF, ~47K WOFF, ~34K WOFF2.

## Where to get a pIqaD font

There is no canonical or universally licensed pIqaD font, because the script
is not in standard Unicode and lives in the **Private Use Area** (U+F8D0 –
U+F8F9, KLI registry). The plugin's transliterator is hard-coded against the
KLI codepoint mapping, so any pIqaD font using those PUA assignments works.

Fonts known to use the KLI PUA mapping:

- **pIqaD HaSta** — Klingon Language Institute
  <https://www.kli.org/about-klingon/klingon-fonts/>  (free, ships as `.ttf`;
  what's bundled here when present)
- **Code2000** — covers the Klingon PUA range as part of a much larger glyph set
- **Klingon pIqaD** by Michael Everson (Evertype) — commercial license

Download the font, rename it to `pIqaD.ttf` (and optionally generate the
`.woff2` / `.woff` derivatives as shown above), then place the file(s) in
this folder.

## Codepoint reference

The plugin maps Latin Klingon to PUA codepoints like this:

| Latin | Codepoint | Latin | Codepoint |
|-------|-----------|-------|-----------|
| `a`   | U+F8D0    | `p`   | U+F8DE    |
| `b`   | U+F8D1    | `q`   | U+F8DF    |
| `ch`  | U+F8D2    | `Q`   | U+F8E0    |
| `D`   | U+F8D3    | `r`   | U+F8E1    |
| `e`   | U+F8D4    | `S`   | U+F8E2    |
| `gh`  | U+F8D5    | `t`   | U+F8E3    |
| `H`   | U+F8D6    | `tlh` | U+F8E4    |
| `I`   | U+F8D7    | `u`   | U+F8E5    |
| `j`   | U+F8D8    | `v`   | U+F8E6    |
| `l`   | U+F8D9    | `w`   | U+F8E7    |
| `m`   | U+F8DA    | `y`   | U+F8E8    |
| `n`   | U+F8DB    | `'`   | U+F8E9    |
| `ng`  | U+F8DC    | `0`–`9` | U+F8F0–U+F8F9 |
| `o`   | U+F8DD    |       |           |

If your font uses a different mapping (e.g. Mandel-script encoding), it won't
render correctly with this plugin. Stick to KLI-encoded fonts.
