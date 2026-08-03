/**
 * Renders the WildWatch geometric "W" logo, matching the mobile app's icon.
 * Composed of two mountain-like peaks representing nature and vigilance.
 */
export function Logo({ size = 22, className }: { size?: number; className?: string }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 128 108"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      className={className}
    >
      <path
        d="M22 76L48 32L74 76"
        stroke="#EE921A"
        strokeWidth="14"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <path
        d="M58 76L80 48L102 76"
        stroke="#EE921A"
        strokeWidth="10"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  );
}
