export const PIG_CYCLE_DAYS = {
    gestacao: 114,
    lactacao: 21,
    intervalo: 7,
    recria: 63,
    terminacao: 70,
};

export function calculatePigCycle(coverageDate, referenceDate = new Date(), calendarType = 'gregoriano', config = {}) {
    const durations = calendarType === '1000_dias' ? PIG_CYCLE_DAYS : {
        gestacao: config.gestacao_dias || 114,
        lactacao: config.lactacao_max_dias || 21,
        intervalo: config.intervalo_desmame_cio_dias || 7,
        recria: 63,
        terminacao: 70,
    };

    const start = new Date(coverageDate);
    const ref = new Date(referenceDate);

    const expectedBirthDate = new Date(start);
    expectedBirthDate.setDate(start.getDate() + durations.gestacao);

    const weaningDate = new Date(expectedBirthDate);
    weaningDate.setDate(expectedBirthDate.getDate() + durations.lactacao);

    const nextCoverageDate = new Date(weaningDate);
    nextCoverageDate.setDate(weaningDate.getDate() + durations.intervalo);

    const rearEndDate = new Date(weaningDate);
    rearEndDate.setDate(weaningDate.getDate() + durations.recria);

    const slaughterDate = new Date(rearEndDate);
    slaughterDate.setDate(rearEndDate.getDate() + durations.terminacao);

    const diffDays = (d1, d2) => Math.floor((d2 - d1) / (1000 * 60 * 60 * 24));

    const totalDaysElapsed = diffDays(start, ref);
    let currentPhase = 'concluido';
    let daysInPhase = 0;
    let daysRemainingInPhase = 0;

    if (ref < expectedBirthDate) {
        currentPhase = 'gestacao';
        daysInPhase = diffDays(start, ref);
        daysRemainingInPhase = diffDays(ref, expectedBirthDate);
    } else if (ref < weaningDate) {
        currentPhase = 'lactacao';
        daysInPhase = diffDays(expectedBirthDate, ref);
        daysRemainingInPhase = diffDays(ref, weaningDate);
    } else if (ref < nextCoverageDate) {
        currentPhase = 'intervalo';
        daysInPhase = diffDays(weaningDate, ref);
        daysRemainingInPhase = diffDays(ref, nextCoverageDate);
    } else if (ref < rearEndDate) {
        currentPhase = 'recria';
        daysInPhase = diffDays(weaningDate, ref);
        daysRemainingInPhase = diffDays(ref, rearEndDate);
    } else if (ref < slaughterDate) {
        currentPhase = 'terminacao';
        daysInPhase = diffDays(rearEndDate, ref);
        daysRemainingInPhase = diffDays(ref, slaughterDate);
    }

    return {
        coverageDate: start,
        expectedBirthDate,
        weaningDate,
        nextCoverageDate,
        rearEndDate,
        slaughterDate,
        currentPhase,
        daysInPhase,
        daysRemainingInPhase,
        totalDaysElapsed
    };
}
