---
name: sync-github
description: Синхронизирует внутренний репозиторий Mcp с публичным GitHub. Используй для публикации обновлений.
disable-model-invocation: true
---

# Sync Mcp to GitHub

Синхронизирует внутренний репозиторий `../Mcp` с публичным GitHub репозиторием `git@github.com:Freento/Mcp.git`.

## Исключения при синхронизации:
- `*.md` — все markdown файлы в корне (внутренняя документация)
- `*.html` — все HTML файлы
- `.git` — отдельная git история для GitHub

## Специальная обработка:
- `USER_GUIDE_GITHUB.md` копируется и переименовывается в `README.md`

## Алгоритм выполнения:

### 1. Проверка незакоммиченных изменений

Перед началом проверь `git status` в текущей директории (Mcp-github).
Если есть незакоммиченные изменения — сообщи пользователю и спроси, продолжать ли.

### 2. Pull в исходном репозитории

```bash
cd ../Mcp && git pull
```

### 3. Копирование файлов

```bash
rsync -av --delete \
    --exclude='*.md' \
    --exclude='*.html' \
    --exclude='.git' \
    ../Mcp/ ./

cp ../Mcp/USER_GUIDE_GITHUB.md ./README.md
```

### 4. Проверка и коммит

Покажи `git status` пользователю.

**ВАЖНО:** НЕ добавляй "Co-Authored-By" в коммиты.

```bash
git add -A
git commit -m "Sync {текущая дата в формате YYYY-MM-DD}"
```

### 5. Release tag (опционально)

Спроси пользователя, нужно ли создать release tag.
Если да — спроси версию и создай:

```bash
git tag -a v{версия} -m "Release {версия}"
```

### 6. Push

```bash
git push origin main
git push origin --tags   # если был создан tag
```
