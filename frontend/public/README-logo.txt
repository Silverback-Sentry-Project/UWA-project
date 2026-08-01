Drop your UWA logo file here as: logo.png (or logo.svg)

Anything placed in this /public folder is served from the site root.
So public/logo.png becomes available at /logo.png in the app.

The portal reads /logo.png in two places:
  - src/routes/portal.index.tsx   (login screen)
  - src/components/portal/PortalShell.tsx  (sidebar header)

If the file is missing, both spots fall back to the leaf icon automatically,
so nothing breaks if you haven't added it yet.
