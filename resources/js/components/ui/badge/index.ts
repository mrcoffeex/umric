import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-full border px-2.5 py-0.5 text-xs font-semibold w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,background-color,border-color,box-shadow] duration-200 overflow-hidden",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground shadow-xs [a&]:hover:bg-primary/80",
        secondary:
          "border-border/60 bg-secondary text-secondary-foreground shadow-xs [a&]:hover:bg-secondary/80",
        destructive:
          "border-transparent bg-destructive/15 text-destructive dark:bg-destructive/20 dark:text-red-400 [a&]:hover:bg-destructive/25 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40",
        outline:
          "border-border/60 text-muted-foreground bg-transparent [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
        success:
          "border-transparent bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 [a&]:hover:bg-emerald-200 dark:[a&]:hover:bg-emerald-950/60",
        warning:
          "border-transparent bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 [a&]:hover:bg-amber-200 dark:[a&]:hover:bg-amber-950/60",
        info:
          "border-transparent bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 [a&]:hover:bg-blue-200 dark:[a&]:hover:bg-blue-950/60",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
