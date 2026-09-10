# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Primary user: a CSIT student intern logging a 16-week internship. They work at a placement (e.g. hospital/IT unit), keep an official Excel activity log for assessment, and need a faster, clearer way to enter daily work and weekly summaries without fighting the spreadsheet layout.

Secondary: none confirmed. Supervisors may review the Excel workbook outside this app; the UI is for the intern.

## Product Purpose

Turn the official CSIT Internship Activity Log Excel workbook into a focused web workflow: see week progress, edit student/placement profile, record Mon–Fri daily activities, and save weekly summaries that write back to the same files assessors already expect.

Success: the intern can complete a day’s entry and a week’s summary without opening Excel, while the workbook remains the source of truth for submission.

## Positioning

Excel-first, not a separate database of internship logs. The product mirrors and writes the CSIT workbook (cover page + WEEK-1…16) and `Daily_Reports.xlsx`, so the deliverable for school stays the official file format.

## Operating Context

- Local Docker/Sail stack (PHP/Laravel, MySQL, Redis, Mailpit)
- Two root-level workbooks (gitignored): activity log + daily reports
- Sequential weeks: week N unlocks after week N−1 is complete (summary + ≥5 daily logs)
- Typical session: open dashboard → open unlocked week → add dailies → save weekly summary

## Capabilities and Constraints

- Read/write COVER-PAGE profile (name, reg, company, supervisor, email, start date, signature image)
- Dashboard of 16 weeks with Pending / In Progress / Completed / Locked
- Weekly form: date range, days present/absent, summary
- Daily CRUD into `Daily_Reports.xlsx` with week date-range validation
- No authentication product requirement (local single-user tool)
- Must preserve Excel compatibility with the official CSIT sheet structure
- Undecided: multi-user/cloud hosting, supervisor login

## Brand Commitments

Product name in UI: **CSIT Internship Activity Log** (readable form of CSIT-Internship-Activity-Log). Voice: plain, direct, academic-admin — no marketing hype.

## Evidence on Hand

- Sample workbook: Downloads `CSIT-Internship Activity Log Yankho Chisale.xlsx` (profile + sheet structure)
- App routes and Excel controller in this Laravel repo
- No testimonials or marketing assets; do not invent them

## Product Principles

1. The Excel file is the product of record; the UI is a better editor.
2. One clear next action: the current unlocked week.
3. Status language must match assessment rules (5 dailies + summary = complete).
4. Prefer calm Operate UI over decorative dashboard chrome.
5. Failures name the file/problem and how to recover (close Excel, restore workbook).

## Accessibility & Inclusion

Target WCAG AA for contrast and keyboard use on forms, modals, and week navigation. No product-specific assistive requirement beyond that was established.
