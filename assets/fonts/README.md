# pIqaD font

To enable Klingon **pIqaD** rendering, drop a pIqaD font file in this folder.
The plugin will look for either of these filenames:

- `pIqaD.woff2` (preferred — smaller, modern browsers)
- `pIqaD.ttf` (fallback)

If neither file is present, the *Render in pIqaD script* admin setting will
still appear but show a notice asking for a font, and the body will fall back
to the system font (Latin Klingon stays readable).

## Where to get a pIqaD font

There is no canonical or universally licensed pIqaD font, because the script
is not in standard Unicode and lives in the **Private Use Area** (U+F8D0 –
U+F8F9, KLI registry). The plugin's transliterator is hard-coded against the
KLI codepoint mapping, so any pIqaD font that uses those PUA assignments
will work.

Fonts known to use the KLI PUA mapping:

- **pIqaD HaSta** — Klingon Language Institute
  <https://www.kli.org/about-klingon/klingon-fonts/>
- **Code2000** — covers the Klingon PUA range as part of a much larger glyph set
- **Klingon pIqaD** by Michael Everson (Evertype) — commercial license

Download the font, rename it to `pIqaD.woff2` (or `pIqaD.ttf`), and place it
in this folder. Then go to *Settings → General → Klingon Display* and enable
**Render Klingon in pIqaD script**.

## Verifying the codepoints

The plugin maps Latin Klingon to PUA codepoints like this:

| Latin | Codepoint |
|-------|-----------|
| `a`   | U+F8D0    |
| `b`   | U+F8D1    |
| `ch`  | U+F8D2    |
| `D`   | U+F8D3    |
| `e`   | U+F8D4    |
| `gh`  | U+F8D5    |
| `H`   | U+F8D6    |
| `I`   | U+F8D7    |
| `j`   | U+F8D8    |
| `l`   | U+F8D9    |
| `m`   | U+F8DA    |
| `n`   | U+F8DB    |
| `ng`  | U+F8DC    |
| `o`   | U+F8DD    |
| `p`   | U+F8DE    |
| `q`   | U+F8DF    |
| `Q`   | U+F8E0    |
| `r`   | U+F8E1    |
| `S`   | U+F8E2    |
| `t`   | U+F8E3    |
| `tlh` | U+F8E4    |
| `u`   | U+F8E5    |
| `v`   | U+F8E6    |
| `w`   | U+F8E7    |
| `y`   | U+F8E8    |
| `'`   | U+F8E9    |
| `0`–`9` | U+F8F0–U+F8F9 |

If your font uses a different mapping (e.g. Mandel-script encoding), it won't
render correctly with this plugin. Stick to KLI-encoded fonts.
