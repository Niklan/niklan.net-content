# Markdown

The project uses the [CommonMark Markdown specification][common-mark] with a few
customizations.

## Changes to CommonMark

### Fenced Code Blocks

In addition to the language hint, after the fenced code, there is an option to
pass a valid JSON string with additional information.

Currently supported additional information:

- `highlighted_lines`: A string of code lines to be highlighted, with multiple
  values separated by semicolons. Ranges can be specified using dashes. For
  example: `1-5;8;10-20`.
- `header`: A header for a code block, mainly intended to be used as the name of
  the file from which a code example is taken.

````markdown
```php {"header":"hello-world.php","highlighted_lines":"3"}
<?php

echo "Hello, World!";
```
````

## Generic directives

This syntax is heavily inspired by [this discussion][generic-directives]. 
Currently, only container and leaf directives are implemented, mainly because
there is no need for inline ones.

### Leaf block directives

The syntax of the leaf block directive is:

```markdown
:: name [inline-content] (argument) {#id .class key=val}
```

Think of leaf directives as blocks of content that are self-contained and can
only be used on a single line without any other syntax or markup. They are not
nested and do not have child elements. The closest HTML example is `<iframe>`
tag.

- Spaces are optional between different parts. `::name[foo]{bar=baz}` is the
  same as `:: name [foo] {bar=baz}`.
- By default, only a name is required, but different use cases may have their
  own specific requirements and limitations.
- It always starts with a double colon.

### Video

Used to embed a local video file that will be loaded on the website and served
using the `<video>` tag.

```markdown
:: video [Video title] (./path/to/video.mp4)
```

- `[]`: (required) The video title will be used to save the file.
- `()`: (required) The path to the video file. The path should be local;
  external videos will not be downloaded.

### YouTube video

Used to embed a YouTube video on the page.

```markdown
:: youtube {vid=4Ru8DMW-grY}
```

- `{}`
  - `vid`: (required) The YouTube video ID.

## Container block directives

The syntax of the container block directive is:

```markdown
::: name [inline-content] (argument) {#id .class key=val}
  Container content.
:::
```

Container block directives work as they sound: they expect content to be placed
inside the container. Think of them as `<div>` elements with different types of
content.

- Spaces are optional between different parts. `:::name[foo]{bar=baz}` is the
  same as `::: name[foo] {bar = baz}`.
- By default, only a name is required, but different use cases may have their
  own specific requirements and limitations.
- It always starts with a triple colon, but that is not limited. You can use
  more than three colon to create a container for children.
- The inner content should always be indented at least as much as the opening
  colon.

```markdown
:::::: first
  :::: second
    This is a fun video!

    :: youtube {vid=123}
  ::::
::::::
```

### Alerts

Alerts can be used to highlight specific information in content. Alerts can be
added using these container directive names: **note**, **tip**, **important**,
**warning**, **caution**.

```markdown
::: important
  This is an important part of the content.
:::

::::: note [This is dangerous!]
  ::: tip
    Actually, it is not dangerous.
  :::
:::::
```

- `[]`: An optional heading. By default, it will use alert type as a heading for
  the section. However, you can override this if you prefer. The heading can
  contain inline Markdown.

### Figure

The figure container is simply replaced by the `<figure>` HTML tag.

```markdown
::: figure
  :: video [example] (video.mp4)
:::
```

### Figcaption

The figcaption container is simply replaced by the `<figcaption>` HTML tag.

```markdown
::::: figure
  ![image](img.png)
  ::: figcaption
    This is an awesome picture!
  :::
:::::
```

[common-mark]: https://commonmark.org/
[generic-directives]: https://talk.commonmark.org/t/generic-directives-plugins-syntax/444
