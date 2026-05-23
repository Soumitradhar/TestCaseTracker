#!/usr/bin/env python3
"""
generate_report.py — TestFlow PDF Report Generator
Usage: python3 generate_report.py input.json output.pdf
"""

import sys
import json
import datetime
from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.lib.units import mm
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table,
    TableStyle, HRFlowable, KeepTogether
)
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_RIGHT

# ── Load payload ───────────────────────────────────────────
if len(sys.argv) < 3:
    print("Usage: generate_report.py input.json output.pdf")
    sys.exit(1)

with open(sys.argv[1], 'r', encoding='utf-8') as f:
    data = json.load(f)

OUT_PDF     = sys.argv[2]
PROJECT     = data.get('project', 'Project')
COLOR_HEX   = data.get('color', '#00B894')
NOTES       = data.get('notes', '')
CASES       = data.get('cases', [])
RUNS        = data.get('runs', [])

accent = colors.HexColor(COLOR_HEX)
dark   = colors.HexColor("#1e1e2e")
gray   = colors.HexColor("#636e72")
light  = colors.HexColor("#f5f6fa")
bdr    = colors.HexColor("#e0e0e8")

W, H = A4

# ── Summary stats ──────────────────────────────────────────
total   = len(CASES)
passed  = sum(1 for c in CASES if c.get('status') == 'pass')
failed  = sum(1 for c in CASES if c.get('status') == 'fail')
skipped = sum(1 for c in CASES if c.get('status') == 'skip')
pending = sum(1 for c in CASES if c.get('status') == 'pending')
pass_rate = round(passed / total * 100) if total else 0

# ── Document ───────────────────────────────────────────────
doc = SimpleDocTemplate(
    OUT_PDF, pagesize=A4,
    rightMargin=18*mm, leftMargin=18*mm,
    topMargin=14*mm, bottomMargin=14*mm
)

CW = W - 36*mm   # content width

# ── Styles ─────────────────────────────────────────────────
def ps(name, **kw):
    return ParagraphStyle(name, **kw)

S = {
    'banner_title': ps('bt', fontName='Helvetica-Bold',  fontSize=20, textColor=colors.white, leading=26),
    'banner_sub':   ps('bs', fontName='Helvetica',       fontSize=9,  textColor=colors.HexColor("#ccfff5"), leading=13),
    'section':      ps('sc', fontName='Helvetica-Bold',  fontSize=12, textColor=dark, leading=16, spaceBefore=14, spaceAfter=7),
    'body':         ps('bd', fontName='Helvetica',       fontSize=9,  textColor=dark, leading=13),
    'small':        ps('sm', fontName='Helvetica',       fontSize=8,  textColor=gray, leading=11),
    'small_bold':   ps('sb', fontName='Helvetica-Bold',  fontSize=8,  textColor=gray, leading=11),
    'tc_title':     ps('tt', fontName='Helvetica-Bold',  fontSize=9,  textColor=dark, leading=12),
    'tc_desc':      ps('td', fontName='Helvetica',       fontSize=8,  textColor=gray, leading=11),
    'tc_id':        ps('ti', fontName='Helvetica-Bold',  fontSize=8,  textColor=gray, leading=11),
    'notes':        ps('nt', fontName='Helvetica',       fontSize=9,  textColor=dark, leading=14, leftIndent=4),
    'footer':       ps('ft', fontName='Helvetica',       fontSize=7,  textColor=colors.HexColor("#b2bec3"), leading=10, alignment=TA_CENTER),
    'run_id':       ps('ri', fontName='Helvetica-Bold',  fontSize=9,  textColor=dark, leading=12),
    'run_val':      ps('rv', fontName='Helvetica',       fontSize=9,  textColor=dark, leading=12),
}

STATUS_COLORS = {
    'pass':    (colors.HexColor("#e0faf4"), colors.HexColor("#007a63")),
    'fail':    (colors.HexColor("#ffeaea"), colors.HexColor("#c0392b")),
    'skip':    (colors.HexColor("#fffbe6"), colors.HexColor("#b8860b")),
    'pending': (colors.HexColor("#f0f0f5"), colors.HexColor("#636e72")),
}
PRIORITY_COLORS = {
    'High':   (colors.HexColor("#ffeaea"), colors.HexColor("#c0392b")),
    'Medium': (colors.HexColor("#fff4d6"), colors.HexColor("#c8860a")),
    'Low':    (colors.HexColor("#e8f5e9"), colors.HexColor("#2e7d32")),
}

def badge(text, bg, tc, w=20*mm):
    bs = ps('bgs', fontName='Helvetica-Bold', fontSize=7, textColor=tc, leading=10, alignment=TA_CENTER)
    t = Table([[Paragraph(str(text), bs)]], colWidths=[w])
    t.setStyle(TableStyle([
        ('BACKGROUND',    (0,0), (-1,-1), bg),
        ('TOPPADDING',    (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('LEFTPADDING',   (0,0), (-1,-1), 3),
        ('RIGHTPADDING',  (0,0), (-1,-1), 3),
        ('ROUNDEDCORNERS',[4]),
    ]))
    return t

def section_line(title):
    return Paragraph(f'<b>{title}</b>', S['section'])

# ── Story ──────────────────────────────────────────────────
story = []

# ── BANNER ────────────────────────────────────────────────
now_str = datetime.datetime.now().strftime('%d %b %Y, %H:%M')
banner_inner = Table([
    [Paragraph(PROJECT, S['banner_title'])],
    [Paragraph(f'Test Report  ·  Generated {now_str}', S['banner_sub'])],
], colWidths=[CW])
banner_inner.setStyle(TableStyle([
    ('TOPPADDING',    (0,0), (-1,-1), 0),
    ('BOTTOMPADDING', (0,0), (-1,-1), 0),
    ('LEFTPADDING',   (0,0), (-1,-1), 0),
    ('RIGHTPADDING',  (0,0), (-1,-1), 0),
]))
banner = Table([[banner_inner]], colWidths=[CW])
banner.setStyle(TableStyle([
    ('BACKGROUND',    (0,0), (-1,-1), accent),
    ('TOPPADDING',    (0,0), (-1,-1), 18),
    ('BOTTOMPADDING', (0,0), (-1,-1), 18),
    ('LEFTPADDING',   (0,0), (-1,-1), 20),
    ('RIGHTPADDING',  (0,0), (-1,-1), 20),
    ('ROUNDEDCORNERS',[8]),
]))
story.append(banner)
story.append(Spacer(1, 12))

# ── NOTES ────────────────────────────────────────────────
story.append(section_line('Report Notes'))
note_text = NOTES if NOTES else 'No notes added for this report.'
notes_box = Table([[Paragraph(note_text, S['notes'])]], colWidths=[CW])
notes_box.setStyle(TableStyle([
    ('BACKGROUND',    (0,0), (-1,-1), colors.HexColor("#f8f9fa")),
    ('BOX',           (0,0), (-1,-1), 0.5, bdr),
    ('TOPPADDING',    (0,0), (-1,-1), 11),
    ('BOTTOMPADDING', (0,0), (-1,-1), 11),
    ('LEFTPADDING',   (0,0), (-1,-1), 14),
    ('RIGHTPADDING',  (0,0), (-1,-1), 14),
    ('ROUNDEDCORNERS',[6]),
]))
story.append(notes_box)
story.append(Spacer(1, 12))

# ── SUMMARY STATS ────────────────────────────────────────
story.append(section_line('Test Summary'))

def stat_cell(val, lbl, bg, vc):
    vs = ps(f'sv{lbl}', fontName='Helvetica-Bold', fontSize=22, textColor=vc, leading=26, alignment=TA_CENTER)
    ls = ps(f'sl{lbl}', fontName='Helvetica',      fontSize=8,  textColor=gray, leading=11, alignment=TA_CENTER)
    inner = Table([[Paragraph(str(val), vs)], [Paragraph(lbl, ls)]], colWidths=[30*mm])
    inner.setStyle(TableStyle([
        ('TOPPADDING',    (0,0), (-1,-1), 1),
        ('BOTTOMPADDING', (0,0), (-1,-1), 1),
        ('LEFTPADDING',   (0,0), (-1,-1), 0),
        ('RIGHTPADDING',  (0,0), (-1,-1), 0),
    ]))
    outer = Table([[inner]], colWidths=[32*mm])
    outer.setStyle(TableStyle([
        ('BACKGROUND',    (0,0), (-1,-1), bg),
        ('TOPPADDING',    (0,0), (-1,-1), 10),
        ('BOTTOMPADDING', (0,0), (-1,-1), 10),
        ('ALIGN',         (0,0), (-1,-1), 'CENTER'),
        ('ROUNDEDCORNERS',[6]),
    ]))
    return outer

stats_row = Table([[
    stat_cell(total,   'Total',   colors.HexColor("#f0f0f8"), dark),
    stat_cell(passed,  'Passed',  colors.HexColor("#e0faf4"), colors.HexColor("#007a63")),
    stat_cell(failed,  'Failed',  colors.HexColor("#ffeaea"), colors.HexColor("#c0392b")),
    stat_cell(skipped, 'Skipped', colors.HexColor("#fffbe6"), colors.HexColor("#b8860b")),
    stat_cell(pending, 'Pending', colors.HexColor("#f0f0f5"), gray),
]], colWidths=[33*mm]*5)
stats_row.setStyle(TableStyle([
    ('ALIGN',         (0,0), (-1,-1), 'CENTER'),
    ('VALIGN',        (0,0), (-1,-1), 'MIDDLE'),
    ('LEFTPADDING',   (0,0), (-1,-1), 2),
    ('RIGHTPADDING',  (0,0), (-1,-1), 2),
]))
story.append(stats_row)
story.append(Spacer(1, 10))

# Pass rate bar
rate_color = (colors.HexColor("#007a63") if pass_rate >= 80
              else colors.HexColor("#b8860b") if pass_rate >= 50
              else colors.HexColor("#c0392b"))
rate_s = ps('rts', fontName='Helvetica-Bold', fontSize=12, textColor=rate_color, leading=16)
rate_box = Table([[Paragraph(f'Overall Pass Rate: {pass_rate}%  ({passed}/{total} tests passed)', rate_s)]], colWidths=[CW])
rate_box.setStyle(TableStyle([
    ('BACKGROUND',    (0,0), (-1,-1), colors.HexColor("#f8f9fa")),
    ('BOX',           (0,0), (-1,-1), 1.2, rate_color),
    ('TOPPADDING',    (0,0), (-1,-1), 9),
    ('BOTTOMPADDING', (0,0), (-1,-1), 9),
    ('LEFTPADDING',   (0,0), (-1,-1), 16),
    ('ROUNDEDCORNERS',[6]),
]))
story.append(rate_box)
story.append(Spacer(1, 12))

# ── TEST CASES TABLE ────────────────────────────────────────
story.append(section_line(f'Test Cases ({total})'))

hdr_s = ps('hdr', fontName='Helvetica-Bold', fontSize=8, textColor=gray, leading=11)

tc_rows = [[
    Paragraph('ID', hdr_s),
    Paragraph('Title & Description', hdr_s),
    Paragraph('Priority', hdr_s),
    Paragraph('Status', hdr_s),
    Paragraph('Updated', hdr_s),
]]

for c in CASES:
    sbg, stc = STATUS_COLORS.get(c.get('status','pending'), STATUS_COLORS['pending'])
    pbg, ptc = PRIORITY_COLORS.get(c.get('priority','Medium'), PRIORITY_COLORS['Medium'])
    title_cell = Table([
        [Paragraph(c.get('title',''), S['tc_title'])],
        [Paragraph(c.get('desc','') or '', S['tc_desc'])],
    ], colWidths=[86*mm])
    title_cell.setStyle(TableStyle([
        ('TOPPADDING',    (0,0), (-1,-1), 1),
        ('BOTTOMPADDING', (0,0), (-1,-1), 1),
        ('LEFTPADDING',   (0,0), (-1,-1), 0),
        ('RIGHTPADDING',  (0,0), (-1,-1), 0),
    ]))
    updated = ''
    if c.get('updated_at'):
        try:
            dt = str(c['updated_at'])[:10]
            updated = datetime.datetime.strptime(dt, '%Y-%m-%d').strftime('%d %b %Y')
        except:
            updated = str(c.get('updated_at',''))[:10]
    tc_rows.append([
        Paragraph(c.get('tc_id',''), S['tc_id']),
        title_cell,
        badge(c.get('priority','Medium'), pbg, ptc, 20*mm),
        badge(c.get('status','pending').upper(), sbg, stc, 20*mm),
        Paragraph(updated, S['small']),
    ])

tc_table = Table(tc_rows, colWidths=[16*mm, 88*mm, 22*mm, 22*mm, 24*mm], repeatRows=1)
tc_table.setStyle(TableStyle([
    ('BACKGROUND',    (0,0), (-1,0), light),
    ('LINEBELOW',     (0,0), (-1,0), 1,   bdr),
    ('LINEBELOW',     (0,1), (-1,-1), 0.4, colors.HexColor("#f0f0f0")),
    ('TOPPADDING',    (0,0), (-1,-1), 8),
    ('BOTTOMPADDING', (0,0), (-1,-1), 8),
    ('LEFTPADDING',   (0,0), (-1,-1), 10),
    ('RIGHTPADDING',  (0,0), (-1,-1), 6),
    ('VALIGN',        (0,0), (-1,-1), 'MIDDLE'),
    ('BOX',           (0,0), (-1,-1), 0.5, bdr),
]))
story.append(tc_table)
story.append(Spacer(1, 14))

# ── RUN HISTORY ────────────────────────────────────────────
if RUNS:
    story.append(section_line(f'Run History ({len(RUNS)} runs)'))
    run_rows = [[
        Paragraph('Run ID', hdr_s),
        Paragraph('Date & Time', hdr_s),
        Paragraph('Total', hdr_s),
        Paragraph('Pass', hdr_s),
        Paragraph('Fail', hdr_s),
        Paragraph('Skip', hdr_s),
        Paragraph('Pending', hdr_s),
        Paragraph('Pass Rate', hdr_s),
    ]]
    for r in RUNS:
        rt = round(r.get('pass',0) / r.get('total',1) * 100) if r.get('total',0) else 0
        rc = (colors.HexColor("#007a63") if rt>=80
              else colors.HexColor("#b8860b") if rt>=50
              else colors.HexColor("#c0392b"))
        rrs = ps(f'rr{r["run_id"]}', fontName='Helvetica-Bold', fontSize=9, textColor=rc, leading=12)

        def col(val, c=dark):
            cs = ps(f'cv{val}', fontName='Helvetica', fontSize=9, textColor=c, leading=12)
            return Paragraph(str(val), cs)

        created = ''
        try:
            ct = str(r.get('created_at',''))
            created = ct[:16] if len(ct)>=16 else ct
        except: pass

        run_rows.append([
            Paragraph(r.get('run_id',''), S['run_id']),
            Paragraph(created, S['small']),
            col(r.get('total',0)),
            col(r.get('pass',0),  colors.HexColor("#007a63")),
            col(r.get('fail',0),  colors.HexColor("#c0392b")),
            col(r.get('skip',0),  colors.HexColor("#b8860b")),
            col(r.get('pending',0)),
            Paragraph(f'{rt}%', rrs),
        ])

    run_table = Table(run_rows, colWidths=[22*mm, 38*mm, 14*mm, 14*mm, 14*mm, 14*mm, 16*mm, 18*mm], repeatRows=1)
    run_table.setStyle(TableStyle([
        ('BACKGROUND',    (0,0), (-1,0), light),
        ('LINEBELOW',     (0,0), (-1,0), 1,   bdr),
        ('LINEBELOW',     (0,1), (-1,-1), 0.4, colors.HexColor("#f0f0f0")),
        ('TOPPADDING',    (0,0), (-1,-1), 8),
        ('BOTTOMPADDING', (0,0), (-1,-1), 8),
        ('LEFTPADDING',   (0,0), (-1,-1), 8),
        ('RIGHTPADDING',  (0,0), (-1,-1), 6),
        ('VALIGN',        (0,0), (-1,-1), 'MIDDLE'),
        ('BOX',           (0,0), (-1,-1), 0.5, bdr),
    ]))
    story.append(run_table)
    story.append(Spacer(1, 18))

# ── FOOTER ────────────────────────────────────────────────
story.append(HRFlowable(width="100%", thickness=0.5, color=bdr))
story.append(Spacer(1, 5))
story.append(Paragraph(
    f'TestFlow  ·  {PROJECT}  ·  Confidential  ·  {datetime.datetime.now().strftime("%d %b %Y")}',
    S['footer']
))

# ── Build ─────────────────────────────────────────────────
doc.build(story)
print(f"PDF generated: {OUT_PDF}")
