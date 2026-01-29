#!/bin/bash
# VS Code拡張機能の一括インストールスクリプト

echo "Installing VS Code extensions..."

extensions=(
    "aaron-bond.better-comments"
    "celianriboulet.webvalidator"
    "ecmel.vscode-html-css"
    "esbenp.prettier-vscode"
    "formulahendry.auto-rename-tag"
    "github.copilot"
    "github.copilot-chat"
    "mosapride.zenkaku"
    "ms-ceintl.vscode-language-pack-ja"
    "streetsidesoftware.code-spell-checker"
    "tabnine.tabnine-vscode"
    "vue.volar"
)

for ext in "${extensions[@]}"; do
    echo "Installing $ext..."
    code --install-extension "$ext"
done

echo "✅ All extensions installed successfully!"
