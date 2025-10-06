export class BoardService {
    constructor(cardsProvider, similarity) {
        this.cardsProvider = cardsProvider;
        this.similarity = similarity;
    }
    async loadBoard(inputs) {
        try {
            const cards = await this.cardsProvider();
            const filtered = this.filterCards(cards, inputs);
            const canvas = this.createCanvas(filtered);
            const relationGraph = this.createRelationGraph(filtered);
            return {
                canvas,
                relationGraph,
                state: 'success',
                templatesCtaVisible: filtered.length === 0,
            };
        }
        catch (error) {
            return {
                canvas: { cards: [], groups: [] },
                relationGraph: { nodes: [], edges: [] },
                state: 'failure',
                templatesCtaVisible: false,
            };
        }
    }
    filterCards(cards, inputs) {
        return cards.filter((card) => {
            if (inputs.tagIds && inputs.tagIds.length) {
                const hasTag = card.tags?.some((tag) => inputs.tagIds?.includes(tag.id));
                if (!hasTag)
                    return false;
            }
            if (inputs.eventIds && inputs.eventIds.length && !inputs.eventIds.includes(card.eventId ?? '')) {
                return false;
            }
            if (inputs.ownerIds && inputs.ownerIds.length && !inputs.ownerIds.includes(card.createdBy.id)) {
                return false;
            }
            return true;
        });
    }
    createCanvas(cards) {
        const canvasCards = cards.map((card, index) => ({
            ...card,
            position: {
                x: (index % 5) * 320,
                y: Math.floor(index / 5) * 200,
            },
            memo: '',
        }));
        return { cards: canvasCards, groups: [] };
    }
    createRelationGraph(cards) {
        const nodes = cards.map((card) => ({ cardId: card.id, degree: 0 }));
        const edges = [];
        for (let i = 0; i < cards.length; i += 1) {
            for (let j = i + 1; j < cards.length; j += 1) {
                const weight = this.similarity(cards[i], cards[j]);
                if (weight > 0.35) {
                    edges.push({ from: cards[i].id, to: cards[j].id, weight });
                    nodes[i].degree += 1;
                    nodes[j].degree += 1;
                }
            }
        }
        return { nodes, edges, density: edges.length / (cards.length || 1) };
    }
    applyTemplate(inputs, summarizer) {
        return Promise.all(inputs.slots.map(async (slot) => ({ ...slot, summary: await summarizer(slot) })))
            .then((slots) => {
            const summary = generateSynthesisSummary(inputs.templateType, slots);
            return {
                slotSummaries: slots,
                synthesisSummary: summary,
                state: 'success',
            };
        })
            .catch(() => ({
            slotSummaries: inputs.slots,
            synthesisSummary: '',
            state: 'failure',
        }));
    }
}
export function generateSynthesisSummary(template, slots) {
    const sections = slots
        .map((slot) => `【${slot.label}】\n${(slot.summary ?? '').slice(0, 200)}`)
        .join('\n\n');
    const header = template === '4P' ? '4P分析まとめ' : 'SWOT分析まとめ';
    return `${header}\n${sections}`.slice(0, 600);
}
export function createTagSimilarity() {
    return (a, b) => {
        const tagsA = new Set((a.tags ?? []).map((tag) => tag.id));
        const tagsB = new Set((b.tags ?? []).map((tag) => tag.id));
        if (!tagsA.size || !tagsB.size)
            return 0;
        const intersection = [...tagsA].filter((id) => tagsB.has(id)).length;
        const union = new Set([...tagsA, ...tagsB]).size;
        return intersection / union;
    };
}
export function createEventSimilarity(weight) {
    return (a, b) => (a.eventId && a.eventId === b.eventId ? weight : 0);
}
export function combineSimilarities(...fns) {
    return (a, b) => fns.reduce((score, fn) => Math.max(score, fn(a, b)), 0);
}
//# sourceMappingURL=boardService.js.map