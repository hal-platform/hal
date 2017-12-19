function generateIcon(type) {
    return `<svg class="icon">
        <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="/icons.svg#${type}"></use>
    </svg>`
}

export { generateIcon };
