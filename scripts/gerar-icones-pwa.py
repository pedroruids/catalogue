"""Ícones PROVISÓRIOS da PWA (BRAND.md: logótipo oficial em falta).
Quadrado na cor da marca (token --color-brand) com a inicial em branco; não é um logótipo.
Uso: python3 scripts/gerar-icones-pwa.py   (precisa de Pillow; substituir quando houver logótipo)."""
from PIL import Image, ImageDraw, ImageFont

BRAND = (0x1E, 0x3A, 0x5F)  # --color-brand, resources/css/tokens.css
FONT = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'


def icone(tamanho: int, fracao: float, destino: str) -> None:
    img = Image.new('RGB', (tamanho, tamanho), BRAND)
    fonte = ImageFont.truetype(FONT, int(tamanho * fracao))
    ImageDraw.Draw(img).text((tamanho / 2, tamanho / 2), 'M', font=fonte, fill='white', anchor='mm')
    img.save(destino, optimize=True)


icone(192, 0.55, 'public/icons/icon-192.png')
icone(512, 0.55, 'public/icons/icon-512.png')
icone(512, 0.40, 'public/icons/icon-maskable-512.png')  # zona segura: glifo dentro dos 80% centrais
icone(180, 0.55, 'public/icons/apple-touch-icon-180.png')  # iOS aplica os cantos arredondados
