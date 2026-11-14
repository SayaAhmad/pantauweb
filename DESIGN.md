---
name: Pantau
description: External availability and uptime monitoring for operators
colors:
  primary: "#0f766e"
  primary-hover: "#115e59"
  neutral-bg: "#f1f5f9"
  surface: "#ffffff"
  text-main: "#0f172a"
  text-muted: "#64748b"
  border: "#e2e8f0"
  status-up: "#16a34a"
  status-down: "#dc2626"
  status-unknown: "#64748b"
typography:
  body:
    fontFamily: "Plus Jakarta Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
rounded:
  sm: "0.5rem"
  md: "0.75rem"
  lg: "1rem"
spacing:
  sm: "0.5rem"
  md: "1rem"
  lg: "1.5rem"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "#ffffff"
    rounded: "{rounded.sm}"
    padding: "0.5rem 0.875rem"
  card:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.lg}"
    padding: "1.5rem"
---

# Design System

<!-- impeccable:design-schema 1 -->

## Overview

Pantau uses a restrained, high-contrast operational aesthetic built for reliability monitoring. Calm, clear, and functional.

## Colors

- **Primary**: Teal (`#0f766e`), hover (`#115e59`).
- **Surface**: White (`#ffffff`) on Slate-100 canvas (`#f1f5f9`).
- **Text**: Slate-900 primary, Slate-500 muted.
- **Borders**: Slate-200 / Slate-300.
- **States**: Redundant text + badge/dot for Up (Emerald/Green), Down (Rose/Red), Unknown (Slate).

## Typography

- **Font Family**: Plus Jakarta Sans (`--font-sans`).
- Strict operational hierarchy: compact, legible metrics, clear numbers.

## Layout

- Desktop-first responsive layout with off-canvas sidebar navigation for mobile.
- High scanability card grids and tabular status summaries.

## Elevation & Depth

- Low-contrast, subtle borders with `shadow-sm`.
- Focus on clarity over heavy drop shadows.

## Shapes

- Rounded pill badges (`rounded-full`), standard interactive elements (`rounded-lg` / 8px), and generous card containers (`rounded-2xl` / 16px).

## Components

- **Buttons**: `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-ghost`. Minimum touch target 44px (`min-h-11`).
- **Cards**: `.card` with `rounded-2xl border border-slate-200 bg-white shadow-sm`.
- **Inputs**: `.input` with focus rings matched to teal primary.
- **Badges**: `.badge` with status-specific semantic ring and fill.

## Do's and Don'ts

- **Do** show status with text alongside color indicators.
- **Do** preserve Indonesian operational terminology per `GLOSSARY.md`.
- **Don't** use color alone to signal health or failure.
- **Don't** add decorative fluff, gradients, or aggressive motion.
