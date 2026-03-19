#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIAGRAMS_DIR="$ROOT_DIR/docs/diagrams"
RENDERED_DIR="$DIAGRAMS_DIR/rendered"
FLOWS_DIR="$DIAGRAMS_DIR/flows"
CLASSES_DIR="$DIAGRAMS_DIR/classes"
RENDERED_FLOWS_DIR="$RENDERED_DIR/flows"
RENDERED_CLASSES_DIR="$RENDERED_DIR/classes"

MERMAID_IMAGE="${MERMAID_IMAGE:-minlag/mermaid-cli:latest}"
PLANTUML_IMAGE="${PLANTUML_IMAGE:-plantuml/plantuml:latest}"

ensure_prerequisites() {
    if ! command -v docker >/dev/null 2>&1; then
        echo "docker no esta disponible en el entorno." >&2
        exit 1
    fi
}

prepare_directories() {
    mkdir -p "$RENDERED_FLOWS_DIR" "$RENDERED_CLASSES_DIR"
}

render_mermaid() {
    local source_file

    for source_file in "$FLOWS_DIR"/*.mmd; do
        [ -e "$source_file" ] || continue

        local filename
        local output_file

        filename="$(basename "${source_file%.mmd}")"
        output_file="$RENDERED_FLOWS_DIR/$filename.svg"

        docker run --rm \
            -e HOME=/tmp \
            -e XDG_CACHE_HOME=/tmp \
            -e JAVA_TOOL_OPTIONS="-Duser.home=/tmp -Djava.io.tmpdir=/tmp" \
            -u "$(id -u):$(id -g)" \
            -v "$ROOT_DIR:/workdir" \
            -w /workdir \
            "$MERMAID_IMAGE" \
            -i "/workdir/${source_file#$ROOT_DIR/}" \
            -o "/workdir/${output_file#$ROOT_DIR/}"
    done
}

render_plantuml() {
    local source_file

    for source_file in "$CLASSES_DIR"/*.puml; do
        [ -e "$source_file" ] || continue

        docker run --rm \
            -e HOME=/tmp \
            -e XDG_CACHE_HOME=/tmp \
            -e JAVA_TOOL_OPTIONS="-Duser.home=/tmp -Djava.io.tmpdir=/tmp" \
            -u "$(id -u):$(id -g)" \
            -v "$ROOT_DIR:/workdir" \
            -w /workdir \
            "$PLANTUML_IMAGE" \
            -tsvg \
            -output "/workdir/${RENDERED_CLASSES_DIR#$ROOT_DIR/}" \
            "/workdir/${source_file#$ROOT_DIR/}"
    done
}

main() {
    ensure_prerequisites
    prepare_directories
    render_mermaid
    render_plantuml
}

main "$@"
