/**
 * Converts enum cases to select option format.
 * Formats enum names by converting CamelCase to "Camel Case" and underscores to spaces,
 * then applies title case.
 */
export function enumToOptions<T extends { name: string; value: string }>(
    enumCases: T[],
): Array<{ label: string; value: string }> {
    return enumCases.map((enumCase) => {
        // Convert CamelCase to "Camel Case" by adding space before capital letters
        let label = enumCase.name.replace(/([A-Z])/g, ' $1').trim();
        // Replace underscores with spaces
        label = label.replace(/_/g, ' ');
        // Apply title case (capitalize first letter of each word)
        label = label
            .split(' ')
            .map(
                (word) =>
                    word.charAt(0).toUpperCase() + word.slice(1).toLowerCase(),
            )
            .join(' ');

        return {
            label,
            value: enumCase.value,
        };
    });
}
