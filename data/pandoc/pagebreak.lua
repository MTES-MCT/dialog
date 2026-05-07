-- Convert HTML <div> elements styled with `page-break-before` or
-- `page-break-after` into a real DOCX page break, since pandoc does not
-- translate the CSS property when reading HTML.

function Div(el)
    local style = el.attributes.style or ''

    if style:match('page%-break%-before') or style:match('page%-break%-after') then
        return pandoc.RawBlock('openxml', '<w:p><w:r><w:br w:type="page"/></w:r></w:p>')
    end
end
