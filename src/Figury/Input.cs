using System.Globalization;

namespace Figury;

/// <summary>Wczytywanie i walidacja danych wprowadzanych w konsoli.</summary>
internal static class Input
{
    /// <summary>Wczytuje liczbę większą od zera. Dopuszcza zarówno przecinek, jak i kropkę dziesiętną.</summary>
    public static double ReadPositiveDouble(string prompt)
    {
        while (true)
        {
            Console.Write(prompt);
            string? line = Console.ReadLine();

            if (line is null)
            {
                throw new OperationCanceledException();
            }

            string normalized = line.Trim().Replace(',', '.');

            if (double.TryParse(normalized, NumberStyles.Float, CultureInfo.InvariantCulture, out double value)
                && double.IsFinite(value)
                && value is > 0 and <= 1_000_000)
            {
                return value;
            }

            Console.WriteLine("  ! Podaj liczbę większą od zera (maks. 1 000 000), np. 3,5");
        }
    }

    /// <summary>Wczytuje liczbę całkowitą z podanego zakresu.</summary>
    public static int ReadInt(string prompt, int min, int max)
    {
        while (true)
        {
            Console.Write(prompt);
            string? line = Console.ReadLine();

            if (line is null)
            {
                throw new OperationCanceledException();
            }

            if (int.TryParse(line.Trim(), out int value) && value >= min && value <= max)
            {
                return value;
            }

            Console.WriteLine($"  ! Podaj liczbę od {min} do {max}.");
        }
    }

    /// <summary>Wstrzymuje program do naciśnięcia Enter.</summary>
    public static void Pause()
    {
        Console.WriteLine();
        Console.Write("Naciśnij Enter, aby kontynuować...");
        Console.ReadLine();
    }
}
