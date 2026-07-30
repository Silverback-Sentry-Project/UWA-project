import { useState } from "react";
import { Leaf } from "lucide-react";

/**
 * Renders /logo.png (drop your UWA logo file into the public/ folder as
 * logo.png or logo.svg — see public/README-logo.txt). Falls back to the
 * leaf icon automatically if no logo file has been added yet.
 */
export function Logo({ size = 22, className }: { size?: number; className?: string }) {
  const [failed, setFailed] = useState(false);

  if (failed) return <Leaf size={size} className={className} />;

  return (
    <img
      src="/logo.png"
      alt="UWA logo"
      width={size}
      height={size}
      className={className}
      style={{ objectFit: "contain" }}
      onError={() => setFailed(true)}
    />
  );
}
