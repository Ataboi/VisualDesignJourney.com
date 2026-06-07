---
name: Editorial Muse
colors:
  surface: '#faf8ff'
  surface-dim: '#d2d9f4'
  surface-bright: '#faf8ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f3ff'
  surface-container: '#eaedff'
  surface-container-high: '#e2e7ff'
  surface-container-highest: '#dae2fd'
  on-surface: '#131b2e'
  on-surface-variant: '#474552'
  inverse-surface: '#283044'
  inverse-on-surface: '#eef0ff'
  outline: '#787583'
  outline-variant: '#c8c4d4'
  surface-tint: '#5951b4'
  primary: '#574eb1'
  on-primary: '#ffffff'
  primary-container: '#7067cc'
  on-primary-container: '#fffbff'
  inverse-primary: '#c5c0ff'
  secondary: '#5c5f60'
  on-secondary: '#ffffff'
  secondary-container: '#e1e3e4'
  on-secondary-container: '#626566'
  tertiary: '#745800'
  on-tertiary: '#ffffff'
  tertiary-container: '#926f00'
  on-tertiary-container: '#fffbff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e4dfff'
  primary-fixed-dim: '#c5c0ff'
  on-primary-fixed: '#140067'
  on-primary-fixed-variant: '#41379b'
  secondary-fixed: '#e1e3e4'
  secondary-fixed-dim: '#c5c7c8'
  on-secondary-fixed: '#191c1d'
  on-secondary-fixed-variant: '#454748'
  tertiary-fixed: '#ffdf98'
  tertiary-fixed-dim: '#efc04c'
  on-tertiary-fixed: '#251a00'
  on-tertiary-fixed-variant: '#5a4300'
  background: '#faf8ff'
  on-background: '#131b2e'
  surface-variant: '#dae2fd'
  surface-white: '#FFFFFF'
  border-subtle: '#E9ECEF'
  text-muted: '#454F5E'
  accent-soft-purple: '#7F77DD'
typography:
  display-lg:
    fontFamily: Manrope
    fontSize: 64px
    fontWeight: '700'
    lineHeight: '1.1'
    letterSpacing: -0.02em
  headline-xl:
    fontFamily: Manrope
    fontSize: 40px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: -0.01em
  headline-xl-mobile:
    fontFamily: Manrope
    fontSize: 32px
    fontWeight: '600'
    lineHeight: '1.2'
  headline-md:
    fontFamily: Manrope
    fontSize: 24px
    fontWeight: '500'
    lineHeight: '1.4'
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
  label-caps:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: '1'
    letterSpacing: 0.1em
  caption:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: '1.4'
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  container-max: 1440px
  gutter: 24px
  margin-desktop: 48px
  margin-tablet: 32px
  margin-mobile: 16px
  section-gap: 80px
---

## Brand & Style

The design system is rooted in the "Minimalist Editorial" aesthetic, tailored specifically for a community of visual designers and curators. The brand personality is sophisticated, quiet, and intentional, serving as a neutral yet high-end gallery space that allows user-generated mood boards to take center stage. 

The visual language avoids the heavy-handedness of typical social platforms, instead opting for a layout that feels like a premium digital magazine. It prioritizes clarity, extreme whitespace, and a high-fashion sensibility. The emotional response should be one of calm focus and creative inspiration, achieved through precise alignment and a complete lack of visual "noise" like shadows or heavy gradients.

## Colors

This design system utilizes a high-key, restricted palette to emphasize content. The primary color—a soft, desaturated purple (#7F77DD)—is used sparingly for interactive cues, notifications, and brand moments, ensuring it does not compete with the diverse colors within user mood boards.

- **Primary Backgrounds:** Use absolute white (#FFFFFF) for the main canvas.
- **Secondary Surfaces:** Use #F8F9FA for sidebars or secondary containers to create subtle depth without shadows.
- **Borders:** All borders must use a light gray (#E9ECEF) at a 0.5px width to create a "hairline" effect common in print editorial design.
- **Typography:** Headlines and primary text use #0F172A for high legibility, while secondary metadata uses #454F5E.

## Typography

The typography strategy balances the modern, refined structure of **Manrope** for display elements with the utilitarian precision of **Inter** for functional text. 

- **Display & Headlines:** Manrope is used to create a structured, architectural feel. Tighten letter-spacing on larger sizes to maintain a "locked-in" editorial look.
- **Body Text:** Inter is the workhorse for readability. Use standard weights (400) for long-form content to maintain a lightweight feel.
- **Functional Labels:** Use `label-caps` for navigation items and small UI headers to introduce a rhythmic, geometric contrast to the softer body text.

## Layout & Spacing

The layout model is a **Fixed Grid** that prioritizes expansive margins and generous breathing room. 

- **Masonry Grid:** Mood boards are displayed in a fluid masonry configuration within the fixed container. Use a 24px gutter between cards to maintain individual image integrity.
- **Vertical Rhythm:** Sections are separated by large gaps (80px+) to clearly delineate content areas and evoke a gallery-like browsing experience.
- **Breakpoints:**
  - **Desktop (1440px):** 12 columns, 48px margins.
  - **Tablet (768px):** 6 columns, 32px margins.
  - **Mobile (375px):** 2 columns (or 1 column stack), 16px margins.

## Elevation & Depth

This design system explicitly rejects the use of drop shadows. Depth is communicated through **Tonal Layers** and **Subtle Outlines**.

- **Flat Hierarchy:** All elements sit on the same optical plane.
- **0.5px Outlines:** Use `#E9ECEF` borders to define card boundaries and input fields. The thinness of the border ensures the UI feels light and "sketched" rather than heavy.
- **Backdrop Blurs:** When modals are necessary, use a high-density white blur (80% opacity white with 20px blur) to maintain the "Glassmorphism" lightness without traditional shadow-based elevation.

## Shapes

The shape language is a mix of architectural squares and organic pills.

- **Cards & Containers:** Use `rounded-lg` (1rem) for mood board cards to slightly soften the masonry grid.
- **Interactive Elements:** Buttons and tags must be **Pill-shaped** (full radius) to create a distinct contrast against the rectangular content blocks.
- **Avatars:** Strictly circular (50% radius) to differentiate human elements from content elements.
- **Inputs:** Softened with a 0.5rem radius to match the card language.

## Components

### Buttons
- **Primary:** Pill-shaped, background `#7F77DD`, text `#FFFFFF`. No shadow.
- **Secondary:** Pill-shaped, background `transparent`, 0.5px border `#E9ECEF`, text `#0F172A`.
- **Hover States:** Subtle opacity shift (0.8) or a very light gray background fill for secondary buttons.

### Masonry Cards
- **Structure:** 0.5px border, `rounded-lg` corners. 
- **Content:** The image should be flush with the top and sides. Metadata (Title, Designer) sits below the image with 16px of padding.
- **Interaction:** On hover, the 0.5px border changes from `#E9ECEF` to `#7F77DD`.

### Tags & Chips
- Always pill-shaped.
- Background `#F8F9FA` with `label-caps` typography.
- Small 12px horizontal padding.

### Input Fields
- 0.5px border `#E9ECEF`.
- `rounded-md` (0.5rem).
- Focus state: Border color changes to `#7F77DD`.

### Navigation
- Minimalist text links using `label-caps`. 
- Active state indicated by a small 4px dot in `#7F77DD` positioned below the label, rather than an underline.
- Use a persistent, blur-background "Top Bar" that is 100% white with 0.5px bottom border.