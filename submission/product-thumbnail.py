"""Build the WooCommerce.com product thumbnail: exactly 555x416 px, in the product icon's style.

Usage ( from the plugin folder ): python submission/product-thumbnail.py
Writes submission/media/product-thumbnail-555x416.png and .jpg.
"""
import os

from PIL import Image, ImageDraw, ImageFilter, ImageFont

HERE = os.path.dirname(os.path.abspath(__file__))
W, H = 555, 416
S = 3  # Draw at 3x, then scale down, for smooth edges and text.
FONT_BOLD = 'C:/Windows/Fonts/segoeuib.ttf'
FONT = 'C:/Windows/Fonts/segoeui.ttf'

# Diagonal gradient, sampled from product-icon-160.png ( teal top-left to blue bottom-right ).
TL, BR = (0x00, 0xC3, 0xB6), (0x01, 0x71, 0xDF)
w, h = W * S, H * S
grad = Image.new('RGB', (w, h))
px = grad.load()
for y in range(h):
    for x in range(w):
        t = (x / w + y / h) / 2
        px[x, y] = tuple(round(a + (b - a) * t) for a, b in zip(TL, BR))
canvas = grad.convert('RGBA')
draw = ImageDraw.Draw(canvas)

# Product icon, top-left ( its own gradient square, framed in white ).
icon = Image.open(os.path.join(HERE, 'media', 'product-icon-160.png')).convert('RGBA').resize((64 * S, 64 * S), Image.LANCZOS)
mask = Image.new('L', icon.size, 0)
ImageDraw.Draw(mask).rounded_rectangle((0, 0, icon.width - 1, icon.height - 1), radius=14 * S, fill=255)
ring = Image.new('RGBA', (icon.width + 6 * S, icon.height + 6 * S), (0, 0, 0, 0))
ImageDraw.Draw(ring).rounded_rectangle((0, 0, ring.width - 1, ring.height - 1), radius=17 * S, fill=(255, 255, 255, 235))
ring.paste(icon, (3 * S, 3 * S), mask)
canvas.alpha_composite(ring, (30 * S, 30 * S))

# Name and tagline.
title = ImageFont.truetype(FONT_BOLD, 31 * S)
sub = ImageFont.truetype(FONT, 15 * S)
draw.text((30 * S, 112 * S), 'My Account', font=title, fill='white')
draw.text((30 * S, 150 * S), 'Dashboard Builder', font=title, fill='white')
draw.text((31 * S, 196 * S), 'Branded My Account for WooCommerce', font=sub, fill=(255, 255, 255, 225))

# Screenshot card: the real customer dashboard ( menu, offer banner, stats ), bottom-right.
shot = Image.open(os.path.join(HERE, 'docs-media', '01-my-account-dashboard.png')).convert('RGB')
crop = shot.crop((60, 80, 1040, 760))  # Profile card, menu, offer, greeting, stats.
cw = 300 * S
crop = crop.resize((cw, round(crop.height * cw / crop.width)), Image.LANCZOS)
card_w, card_h = crop.width + 16 * S, crop.height + 16 * S
x0, y0 = w - card_w - 22 * S, h - card_h + 70 * S  # Runs off the bottom edge on purpose.

shadow = Image.new('RGBA', (w, h), (0, 0, 0, 0))
ImageDraw.Draw(shadow).rounded_rectangle((x0 + 4 * S, y0 + 10 * S, x0 + card_w + 4 * S, y0 + card_h + 10 * S), radius=14 * S, fill=(0, 30, 80, 90))
canvas.alpha_composite(shadow.filter(ImageFilter.GaussianBlur(14 * S)))

card = Image.new('RGBA', (card_w, card_h), (0, 0, 0, 0))
cd = ImageDraw.Draw(card)
cd.rounded_rectangle((0, 0, card_w - 1, card_h - 1), radius=14 * S, fill='white')
m = Image.new('L', crop.size, 0)
ImageDraw.Draw(m).rounded_rectangle((0, 0, crop.width - 1, crop.height - 1), radius=8 * S, fill=255)
card.paste(crop, (8 * S, 8 * S), m)
canvas.alpha_composite(card, (x0, y0))

out = canvas.convert('RGB').resize((W, H), Image.LANCZOS)
png = os.path.join(HERE, 'media', 'product-thumbnail-555x416.png')
jpg = os.path.join(HERE, 'media', 'product-thumbnail-555x416.jpg')
out.save(png, optimize=True)
out.save(jpg, quality=92, subsampling=0, optimize=True)
for f in (png, jpg):
    print(os.path.basename(f), Image.open(f).size, os.path.getsize(f) // 1024, 'KB')
