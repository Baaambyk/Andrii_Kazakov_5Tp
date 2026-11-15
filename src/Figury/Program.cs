using System.Globalization;
using Figury.Geometry;

namespace Figury;

/// <summary>
/// Aplikacja konsolowa obliczająca pole rombu, obwód koła oraz pole trójkąta.
/// </summary>
internal static class Program
{
    private static readonly CultureInfo Culture = CultureInfo.GetCultureInfo("pl-PL");

    private static void Main()
    {
        Console.OutputEncoding = System.Text.Encoding.UTF8;
        Console.Title = "Figury geometryczne";
        CultureInfo.CurrentCulture = Culture;

        try
        {
            Run();
        }
        catch (OperationCanceledException)
        {
            // Strumień wejściowy został zamknięty (Ctrl+Z lub przekierowanie) — kończymy bez błędu.
        }
    }

    private static void Run()
    {
        while (true)
        {
            ShowMainMenu();
            int choice = Input.ReadInt("Twój wybór: ", 0, 3);

            switch (choice)
            {
                case 0:
                    Console.WriteLine();
                    Console.WriteLine("Do zobaczenia!");
                    return;
                case 1:
                    RhombusMenu();
                    break;
                case 2:
                    CircleMenu();
                    break;
                case 3:
                    TriangleMenu();
                    break;
            }
        }
    }

    private static void ShowMainMenu()
    {
        Console.WriteLine();
        Console.WriteLine("==================================================");
        Console.WriteLine("                FIGURY GEOMETRYCZNE");
        Console.WriteLine("==================================================");
        Console.WriteLine("  1. Pole rombu");
        Console.WriteLine("  2. Obwód koła");
        Console.WriteLine("  3. Pole trójkąta");
        Console.WriteLine("  0. Wyjście");
        Console.WriteLine("--------------------------------------------------");
    }

    private static void RhombusMenu()
    {
        while (true)
        {
            Console.WriteLine();
            Console.WriteLine("--- POLE ROMBU ---");
            Console.WriteLine("  1. Przekątne e i f");
            Console.WriteLine("  2. Bok a i wysokość h");
            Console.WriteLine("  3. Bok a i kąt ostry α");
            Console.WriteLine("  0. Powrót");
            int choice = Input.ReadInt("Twój wybór: ", 0, 3);

            switch (choice)
            {
                case 0:
                    return;

                case 1:
                {
                    double e = Input.ReadPositiveDouble("  Podaj przekątną e: ");
                    double f = Input.ReadPositiveDouble("  Podaj przekątną f: ");
                    double area = Rhombus.AreaFromDiagonals(e, f);
                    PrintResult(
                        "P = (e · f) / 2",
                        $"P = ({N(e)} · {N(f)}) / 2",
                        "Pole rombu",
                        area,
                        "j²");
                    break;
                }

                case 2:
                {
                    double a = Input.ReadPositiveDouble("  Podaj bok a: ");
                    double h = Input.ReadPositiveDouble("  Podaj wysokość h: ");
                    double area = Rhombus.AreaFromSideAndHeight(a, h);
                    PrintResult(
                        "P = a · h",
                        $"P = {N(a)} · {N(h)}",
                        "Pole rombu",
                        area,
                        "j²");
                    break;
                }

                case 3:
                {
                    double a = Input.ReadPositiveDouble("  Podaj bok a: ");
                    double angle = Input.ReadPositiveDouble("  Podaj kąt ostry α (w stopniach): ");

                    if (angle >= 180)
                    {
                        Console.WriteLine("  ! Kąt musi być mniejszy niż 180°.");
                        break;
                    }

                    double area = Rhombus.AreaFromSideAndAngle(a, angle);
                    PrintResult(
                        "P = a² · sin(α)",
                        $"P = {N(a)}² · sin({N(angle)}°)",
                        "Pole rombu",
                        area,
                        "j²");
                    break;
                }
            }

            Input.Pause();
        }
    }

    private static void CircleMenu()
    {
        while (true)
        {
            Console.WriteLine();
            Console.WriteLine("--- OBWÓD KOŁA ---");
            Console.WriteLine("  1. Promień r");
            Console.WriteLine("  2. Średnica d");
            Console.WriteLine("  0. Powrót");
            int choice = Input.ReadInt("Twój wybór: ", 0, 2);

            switch (choice)
            {
                case 0:
                    return;

                case 1:
                {
                    double r = Input.ReadPositiveDouble("  Podaj promień r: ");
                    double length = Circle.Circumference(r);
                    PrintResult(
                        "L = 2 · π · r",
                        $"L = 2 · π · {N(r)}",
                        "Obwód koła",
                        length,
                        "j");
                    break;
                }

                case 2:
                {
                    double d = Input.ReadPositiveDouble("  Podaj średnicę d: ");
                    double length = Circle.CircumferenceFromDiameter(d);
                    PrintResult(
                        "L = π · d",
                        $"L = π · {N(d)}",
                        "Obwód koła",
                        length,
                        "j");
                    break;
                }
            }

            Input.Pause();
        }
    }

    private static void TriangleMenu()
    {
        while (true)
        {
            Console.WriteLine();
            Console.WriteLine("--- POLE TRÓJKĄTA ---");
            Console.WriteLine("  1. Podstawa a i wysokość h");
            Console.WriteLine("  2. Trzy boki a, b, c (wzór Herona)");
            Console.WriteLine("  3. Dwa boki a, b i kąt γ między nimi");
            Console.WriteLine("  0. Powrót");
            int choice = Input.ReadInt("Twój wybór: ", 0, 3);

            switch (choice)
            {
                case 0:
                    return;

                case 1:
                {
                    double a = Input.ReadPositiveDouble("  Podaj podstawę a: ");
                    double h = Input.ReadPositiveDouble("  Podaj wysokość h: ");
                    double area = Triangle.AreaFromBaseAndHeight(a, h);
                    PrintResult(
                        "P = (a · h) / 2",
                        $"P = ({N(a)} · {N(h)}) / 2",
                        "Pole trójkąta",
                        area,
                        "j²");
                    break;
                }

                case 2:
                {
                    double a = Input.ReadPositiveDouble("  Podaj bok a: ");
                    double b = Input.ReadPositiveDouble("  Podaj bok b: ");
                    double c = Input.ReadPositiveDouble("  Podaj bok c: ");

                    if (!Triangle.TryAreaFromSides(a, b, c, out double area))
                    {
                        Console.WriteLine("  ! Z tych boków nie można zbudować trójkąta.");
                        Console.WriteLine("    Każdy bok musi być krótszy od sumy dwóch pozostałych.");
                        break;
                    }

                    double p = (a + b + c) / 2.0;
                    PrintResult(
                        "P = √(p · (p - a) · (p - b) · (p - c)),  p = (a + b + c) / 2",
                        $"P = √({N(p)} · {N(p - a)} · {N(p - b)} · {N(p - c)})",
                        "Pole trójkąta",
                        area,
                        "j²");
                    break;
                }

                case 3:
                {
                    double a = Input.ReadPositiveDouble("  Podaj bok a: ");
                    double b = Input.ReadPositiveDouble("  Podaj bok b: ");
                    double angle = Input.ReadPositiveDouble("  Podaj kąt γ między bokami (w stopniach): ");

                    if (angle >= 180)
                    {
                        Console.WriteLine("  ! Kąt musi być mniejszy niż 180°.");
                        break;
                    }

                    double area = Triangle.AreaFromSidesAndAngle(a, b, angle);
                    PrintResult(
                        "P = (a · b · sin(γ)) / 2",
                        $"P = ({N(a)} · {N(b)} · sin({N(angle)}°)) / 2",
                        "Pole trójkąta",
                        area,
                        "j²");
                    break;
                }
            }

            Input.Pause();
        }
    }

    private static void PrintResult(string formula, string substitution, string label, double value, string unit)
    {
        Console.WriteLine();
        Console.WriteLine($"  Wzór:       {formula}");
        Console.WriteLine($"  Obliczenia: {substitution}");
        Console.WriteLine($"  {label}: {N(value)} {unit}");
    }

    /// <summary>Formatuje liczbę w polskiej notacji (przecinek dziesiętny, maks. 4 miejsca).</summary>
    private static string N(double value) => value.ToString("0.####", Culture);
}
