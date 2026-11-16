#!/usr/bin/env bash
# Khidmah Pro - Hostinger Environment Preflight Invoker
# Redirects to safe, non-destructive diagnostic script.
exec bash "$(dirname "$0")/scripts/hostinger-preflight.sh" "$@"
