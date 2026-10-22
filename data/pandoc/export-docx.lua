-- Filtre Pandoc appliqué lors de l'export DOCX des arrêtés.

-- Convertit les <div> HTML porteurs de `page-break-before` ou `page-break-after`
-- en saut de page DOCX, car Pandoc ne traduit pas cette propriété CSS.
-- On émet un paragraphe quasi invisible (police 1pt, sans espacement) portant
-- `pageBreakBefore` plutôt qu'un <w:br w:type="page"/> : Word ignore
-- `pageBreakBefore` quand le paragraphe se trouve déjà en haut d'une page,
-- ce qui évite d'insérer une page entièrement blanche.
function Div(el)
    local style = el.attributes.style or ''

    if style:match('page%-break%-before') or style:match('page%-break%-after') then
        return pandoc.RawBlock('openxml', '<w:p><w:pPr><w:pageBreakBefore/><w:spacing w:before="0" w:after="0"/><w:rPr><w:sz w:val="2"/><w:szCs w:val="2"/></w:rPr></w:pPr></w:p>')
    end
end

local function contientSeulementDesSautsDeLigne(inlines)
    if #inlines == 0 then
        return true
    end

    for _, inline in ipairs(inlines) do
        if inline.t ~= 'LineBreak' and inline.t ~= 'SoftBreak' and inline.t ~= 'Space' then
            return false
        end
    end

    return true
end

-- Remplace les blocs « vides » (uniquement des sauts de ligne, comme les
-- <p><br></p> produits par l'éditeur Quill) par un paragraphe DOCX vide standard.
-- Sans cela, chaque ligne vide devient un paragraphe contenant un <w:br/>
-- (soit deux lignes de haut) qui hérite du style de sa section (par exemple
-- Dialog_TitrePrincipal), ce qui crée de grands espaces dans le document exporté.
local function normaliserBlocVide(el)
    if contientSeulementDesSautsDeLigne(el.content) then
        return pandoc.RawBlock('openxml', '<w:p/>')
    end
end

Para = normaliserBlocVide
Plain = normaliserBlocVide
Header = normaliserBlocVide
